<?php

namespace App\Http\Controllers\User\ResultChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResultCheckerPageController extends Controller
{
    private float $paystackChargeRate = 0.025;

    public function showCategory(Request $request, string $categorySlug)
    {
        $user = Auth::guard('user')->user();

        $category = DB::table('result_checker_categories')
            ->where('category_slug', $categorySlug)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->first();

        if (! $category) {
            abort(404, 'Result checker category not found.');
        }

        $category->image_url = $this->makeImageUrl(
            $category->category_image ?? null,
            'images/result-checker-placeholder.png'
        );

        $checkers = DB::table('result_checkers as rc')
            ->join('result_checker_vouchers as rcv', function ($join) {
                $join->on('rc.checker_id', '=', 'rcv.checker_id')
                    ->where('rcv.sold_status', '=', 'available');
            })
            ->where('rc.category_id', $category->category_id)
            ->whereRaw('LOWER(rc.status) = ?', ['active'])
            ->select(
                'rc.checker_id',
                'rc.category_id',
                'rc.checker_name',
                'rc.checker_slug',
                'rc.checker_code',
                'rc.checker_type',
                'rc.selling_price',
                'rc.description',
                DB::raw('COUNT(rcv.voucher_id) as available_stock')
            )
            ->groupBy(
                'rc.checker_id',
                'rc.category_id',
                'rc.checker_name',
                'rc.checker_slug',
                'rc.checker_code',
                'rc.checker_type',
                'rc.selling_price',
                'rc.description'
            )
            ->havingRaw('COUNT(rcv.voucher_id) > 0')
            ->orderBy('rc.checker_name')
            ->get()
            ->map(function ($checker) {
                $checker->price = (float) $checker->selling_price;
                $checker->available_stock = (int) $checker->available_stock;

                $checker->discounts = DB::table('result_checker_bulk_discounts')
                    ->where('checker_id', $checker->checker_id)
                    ->whereRaw('LOWER(status) = ?', ['active'])
                    ->select('discount_id', 'min_quantity', 'max_quantity', 'unit_price')
                    ->orderBy('min_quantity')
                    ->get()
                    ->map(function ($discount) {
                        return [
                            'discount_id' => $discount->discount_id,
                            'min_quantity' => (int) $discount->min_quantity,
                            'max_quantity' => $discount->max_quantity ? (int) $discount->max_quantity : null,
                            'unit_price' => (float) $discount->unit_price,
                        ];
                    });

                return $checker;
            });

        $stats = [
            'is_logged_in' => Auth::guard('user')->check(),
            'wallet_balance' => $user ? (float) ($user->wallet_balance ?? 0) : 0,
            'total_checkers' => $checkers->count(),
            'total_available_stock' => $checkers->sum('available_stock'),
            'paystack_charge_rate' => $this->paystackChargeRate,
        ];

        return view('users.result-checkers.show', compact(
            'user',
            'category',
            'checkers',
            'stats'
        ));
    }

    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'checker_id' => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:wallet,paystack'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'digits:10'],
        ]);

        $checker = DB::table('result_checkers')
            ->where('checker_id', $validated['checker_id'])
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->first();

        if (! $checker) {
            return back()->with('error', 'Selected result checker is not available.');
        }

        $availableStock = DB::table('result_checker_vouchers')
            ->where('checker_id', $checker->checker_id)
            ->where('sold_status', 'available')
            ->count();

        if ($availableStock < $validated['quantity']) {
            return back()->with('error', 'Only ' . $availableStock . ' voucher(s) available.');
        }

        $unitPrice = $this->getUnitPrice($checker->checker_id, $validated['quantity'], (float) $checker->selling_price);
        $subtotal = $unitPrice * $validated['quantity'];

        $charge = $validated['payment_method'] === 'paystack'
            ? round($subtotal * $this->paystackChargeRate, 2)
            : 0;

        $totalAmount = round($subtotal + $charge, 2);

        return back()->with('success', 'Order created successfully. Total amount: GH₵' . number_format($totalAmount, 2));
    }

    private function getUnitPrice(string $checkerId, int $quantity, float $defaultPrice): float
    {
        $discount = DB::table('result_checker_bulk_discounts')
            ->where('checker_id', $checkerId)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $discount ? (float) $discount->unit_price : $defaultPrice;
    }

    private function makeImageUrl(?string $path, string $fallback): string
    {
        if (empty($path)) {
            return asset($fallback);
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = 'storage/' . substr($path, 7);
        }

        return asset($path);
    }
}