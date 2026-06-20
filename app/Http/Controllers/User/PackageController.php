<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    public function index(Request $request, string $categorySlug)
    {
        $user = Auth::guard('user')->user();

        $category = DB::table('categories')
            ->where('category_slug', $categorySlug)
            ->first();

        if (!$category) {
            abort(404, 'Category not found.');
        }

        $category->image_url = !empty($category->category_image)
            ? asset($category->category_image)
            : asset('images/category-placeholder.png');

        $categoryIsActive = strtolower((string) ($category->status ?? 'inactive')) === 'active';

        $packages = collect();

        if ($categoryIsActive) {
            $packages = DB::table('plans')
                ->where('category_id', $category->category_id)
                ->where('status', 'active')
                ->select(
                    'plan_id',
                    'category_id',
                    'plan_name',
                    'slug',
                    'network',
                    'data_volume',
                    'selling_price',
                    'description',
                    'status',
                    'created_at'
                )
                ->orderBy('selling_price', 'asc')
                ->orderBy('data_volume', 'asc')
                ->get();
        }

        $walletBalance = $user ? (float) ($user->wallet_balance ?? 0) : 0;

        $paymentStatus = $this->paymentStatus();

        return view('users.packages.index', compact(
            'user',
            'category',
            'packages',
            'categoryIsActive',
            'walletBalance',
            'paymentStatus'
        ));
    }

    public function purchase(Request $request)
    {
        Log::info('[PACKAGE PURCHASE] Request received', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $paymentStatus = $this->paymentStatus();

        if (!$paymentStatus['enabled']) {
            return response()->json([
                'status' => false,
                'message' => $paymentStatus['message'],
            ], 403);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,plan_id'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'network_name' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:paystack,wallet'],
            'email' => ['nullable', 'email', 'max:150'],
            'is_ported' => ['nullable'],
        ]);

        $authUser = Auth::guard('user')->user();
        $isGuest = !$authUser;

        if ($isGuest && empty($validated['email'])) {
            return response()->json([
                'status' => false,
                'message' => 'Email address is required for guest purchase.',
            ], 422);
        }

        $plan = DB::table('plans')
            ->leftJoin('categories', 'plans.category_id', '=', 'categories.category_id')
            ->select(
                'plans.plan_id',
                'plans.category_id',
                'plans.plan_name',
                'plans.network',
                'plans.data_volume',
                'plans.selling_price',
                'plans.status as plan_status',
                'categories.status as category_status'
            )
            ->where('plans.plan_id', $validated['plan_id'])
            ->first();

        if (!$plan) {
            return response()->json([
                'status' => false,
                'message' => 'Selected package was not found.',
            ], 404);
        }

        if (strtolower((string) $plan->plan_status) !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'This package is unavailable.',
            ], 422);
        }

        if (strtolower((string) $plan->category_status) !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'This network is currently out of stock.',
            ], 422);
        }

        $beneficiaryNumber = $this->normalizePhone($validated['recipient_phone']);

        if (!$beneficiaryNumber) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid beneficiary phone number.',
            ], 422);
        }

        $amount = round((float) $plan->selling_price, 2);
        $walletBalance = $authUser ? round((float) ($authUser->wallet_balance ?? 0), 2) : 0;

        return response()->json([
            'status' => true,
            'message' => 'Plan validated successfully.',
            'plan' => [
                'plan_id' => $plan->plan_id,
                'plan_name' => $plan->plan_name,
                'network' => $plan->network,
                'data_volume' => $plan->data_volume,
                'amount' => number_format($amount, 2),
                'raw_amount' => $amount,
                'currency' => 'GHS',
                'recipient_number' => $beneficiaryNumber,
                'wallet_balance' => $walletBalance,
                'can_pay_with_wallet' => !$isGuest && $walletBalance >= $amount,
                'is_guest' => $isGuest,
            ],
        ]);
    }

    private function paymentStatus(): array
    {
        $setting = DB::table('business_settings')->first();

        $paymentSystemEnabled = (int) ($setting->payment_system_enabled ?? 0) === 1;
        $paystackEnabled = (int) ($setting->paystack_enabled ?? 0) === 1;

        $notice = trim((string) ($setting->payment_notice ?? ''));

        if (!$paymentSystemEnabled) {
            return [
                'enabled' => false,
                'title' => 'Payment System Disabled',
                'message' => $notice ?: 'Payments are currently unavailable. Please try again later.',
            ];
        }

        if (!$paystackEnabled) {
            return [
                'enabled' => false,
                'title' => 'Paystack Disabled',
                'message' => $notice ?: 'Paystack payment is currently unavailable. Please try again later.',
            ];
        }

        return [
            'enabled' => true,
            'title' => 'Payment Available',
            'message' => 'Payment is available.',
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $phone);

        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            return $phone;
        }

        if (strlen($phone) === 12 && str_starts_with($phone, '233')) {
            return '0' . substr($phone, 3);
        }

        return null;
    }
}