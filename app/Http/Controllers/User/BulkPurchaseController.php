<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BulkPurchaseController extends Controller
{
    public function index()
    {
        $categories = DB::table('categories')
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get()
            ->map(function ($category) {
                $category->image_url = $this->imageUrl(
                    $category->category_image ?? null,
                    'images/category-placeholder.png'
                );

                return $category;
            });

        return view('users.bulk-purchase.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'string', 'exists:categories,category_id'],
            'category_slug' => ['required', 'string', 'exists:categories,category_slug'],
            'orders_payload' => ['required', 'string'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $authUser = auth('user')->user();

        if (! $authUser) {
            return redirect()
                ->route('user.login')
                ->with('error', 'Please login to continue.');
        }

        $orders = json_decode($validated['orders_payload'], true);

        if (! is_array($orders) || count($orders) < 1) {
            return back()->with('error', 'Please enter at least one valid bulk order.');
        }

        if (count($orders) > 2000) {
            return back()->with('error', 'Maximum 2,000 bulk orders are allowed at once.');
        }

        $category = DB::table('categories')
            ->where('category_id', $validated['category_id'])
            ->where('category_slug', $validated['category_slug'])
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->first();

        if (! $category) {
            return back()->with('error', 'Selected category is not available.');
        }

        $plans = DB::table('plans')
            ->where('category_id', $category->category_id)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->get()
            ->mapWithKeys(function ($plan) {
                return [
                    $this->normalizeCapacity($plan->data_volume) => [
                        'plan_id' => $plan->plan_id,
                        'plan_name' => $plan->plan_name,
                        'network' => $plan->network,
                        'data_volume' => $plan->data_volume,
                        'selling_price' => (float) $plan->selling_price,
                    ],
                ];
            });

        if ($plans->count() < 1) {
            return back()->with('error', 'No active plans found for the selected category.');
        }

        $cleanOrders = [];
        $serverTotal = 0;
        $usedNumbers = [];

        foreach ($orders as $index => $order) {
            $rowNumber = $index + 1;
            $phone = $this->normalizePhone($order['phone'] ?? '');
            $capacity = $this->normalizeCapacity($order['capacity'] ?? '');

            if (! preg_match('/^0\d{9}$/', $phone)) {
                return back()->with('error', 'Invalid phone number detected on row ' . $rowNumber . '.');
            }

            if (isset($usedNumbers[$phone])) {
                return back()->with(
                    'error',
                    "Duplicate number detected: {$phone}. The same number cannot be used more than once in one bulk purchase."
                );
            }

            $usedNumbers[$phone] = true;

            if (! $this->phoneMatchesCategory($phone, $category)) {
                return back()->with('error', "Phone number {$phone} does not match the selected network.");
            }

            if (! $plans->has($capacity)) {
                return back()->with('error', 'Invalid or unavailable plan detected on row ' . $rowNumber . '.');
            }

            $plan = $plans->get($capacity);
            $serverTotal += $plan['selling_price'];

            $cleanOrders[] = [
                'phone' => $phone,
                'plan_id' => $plan['plan_id'],
                'plan_name' => $plan['plan_name'],
                'network' => $plan['network'] ?: ($category->category_name ?? null),
                'capacity' => $plan['data_volume'],
                'price' => $plan['selling_price'],
            ];
        }

        $serverTotal = round($serverTotal, 2);
        $clientTotal = round((float) $validated['total_amount'], 2);

        if ($clientTotal !== $serverTotal) {
            return back()->with('error', 'Purchase total mismatch. Please refresh and try again.');
        }

        try {
            DB::transaction(function () use ($authUser, $category, $cleanOrders, $serverTotal, $validated) {
                $user = DB::table('users')
                    ->where('user_id', $authUser->user_id)
                    ->lockForUpdate()
                    ->first();

                if (! $user) {
                    throw new \Exception('User account not found.');
                }

                $walletBalance = (float) ($user->wallet_balance ?? 0);

                if ($walletBalance < $serverTotal) {
                    throw new \Exception('Insufficient wallet balance for this bulk purchase.');
                }

                DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->update([
                        'wallet_balance' => $walletBalance - $serverTotal,
                        'updated_at' => now(),
                    ]);

                foreach ($cleanOrders as $item) {
                    $orderId = (string) Str::uuid();
                    $orderReference = $this->uniqueReference('orders', 'order_reference', 'BULK');
                    $paymentReference = $this->uniqueReference('payments', 'payment_reference', 'WAL');

                    DB::table('orders')->insert([
                        'order_id' => $orderId,
                        'user_id' => $user->user_id,
                        'plan_id' => $item['plan_id'],
                        'category_id' => $category->category_id,
                        'order_reference' => $orderReference,
                        'network_name' => $item['network'],
                        'plan_name' => $item['plan_name'],
                        'data_size' => $item['capacity'],
                        'beneficiary_number' => $item['phone'],
                        'customer_email' => $user->email ?? null,
                        'amount' => $item['price'],
                        'is_ported' => 0,
                        'status' => 'processing',
                        'admin_notes' => $validated['note'] ?? null,
                        'ordered_at' => now(),
                        'processing_at' => now(),
                        'delivered_at' => null,
                        'cancelled_at' => null,
                        'in_progress_at' => null,
                        'auto_complete_at' => null,
                        'auto_completed' => 0,
                        'completed_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('payments')->insert([
                        'payment_id' => (string) Str::uuid(),
                        'order_id' => $orderId,
                        'user_id' => $user->user_id,
                        'payment_reference' => $paymentReference,
                        'payment_method' => 'wallet',
                        'amount' => $item['price'],
                        'amount_subunit' => (int) round($item['price'] * 100),
                        'charges' => 0,
                        'currency' => 'GHS',
                        'status' => 'successful',
                        'provider' => 'wallet',
                        'provider_reference' => $paymentReference,
                        'provider_transaction_id' => null,
                        'api_response' => json_encode([
                            'status' => true,
                            'message' => 'Wallet payment successful',
                            'reference' => $paymentReference,
                            'order_reference' => $orderReference,
                        ]),
                        'access_code' => null,
                        'authorization_url' => null,
                        'gateway_response' => 'Wallet payment successful',
                        'authorization_code' => null,
                        'verification_response' => json_encode([
                            'status' => true,
                            'message' => 'Payment verified successfully',
                            'channel' => 'wallet',
                        ]),
                        'channel' => 'wallet',
                        'fees' => 0,
                        'customer_email' => $user->email ?? null,
                        'notes' => 'Bulk Order Received: Your ' . $item['capacity'] . ' to ' . $item['phone'] . ' has been accepted.',
                        'paid_at' => now(),
                        'verified_at' => now(),
                        'manual_verified_at' => null,
                        'manual_verification_note' => null,
                        'webhook_received_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            return back()->with(
                'success',
                count($cleanOrders) . ' bulk order(s) submitted successfully. Payment status: successful. Order status: processing. Total deduction: GH₵' . number_format($serverTotal, 2)
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage() ?: 'Unable to complete bulk purchase.');
        }
    }

    private function imageUrl(?string $image, string $fallback): string
    {
        if (empty($image)) {
            return asset($fallback);
        }

        $image = trim($image);

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'storage/')) {
            return asset($image);
        }

        if (str_starts_with($image, 'public/')) {
            return asset('storage/' . substr($image, 7));
        }

        if (str_starts_with($image, 'uploads/')) {
            return asset($image);
        }

        return asset(ltrim($image, '/'));
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = trim((string) $phone);
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);

        if (str_starts_with($phone, '+233')) {
            $phone = '0' . substr($phone, 4);
        }

        $phone = preg_replace('/\D+/', '', $phone);

        if (strlen($phone) === 9) {
            $phone = '0' . $phone;
        }

        if (str_starts_with($phone, '233') && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 3);
        }

        return $phone;
    }

    private function normalizeCapacity(?string $capacity): string
    {
        $capacity = strtoupper(trim((string) $capacity));
        $capacity = str_replace([' ', 'GB'], '', $capacity);

        return $capacity;
    }

    private function phoneMatchesCategory(string $phone, object $category): bool
    {
        $name = strtolower(($category->category_name ?? '') . ' ' . ($category->category_slug ?? ''));

        if (str_contains($name, 'mtn')) {
            return Str::startsWith($phone, ['024', '025', '053', '054', '055', '059']);
        }

        if (str_contains($name, 'telecel') || str_contains($name, 'vodafone')) {
            return Str::startsWith($phone, ['020', '050']);
        }

        if (str_contains($name, 'airteltigo') || str_contains($name, 'airtel') || str_contains($name, 'tigo')) {
            return Str::startsWith($phone, ['026', '027', '056', '057']);
        }

        return true;
    }

    private function uniqueReference(string $table, string $column, string $prefix): string
    {
        do {
            $reference = strtoupper($prefix . Str::random(9));
        } while (DB::table($table)->where($column, $reference)->exists());

        return $reference;
    }
}