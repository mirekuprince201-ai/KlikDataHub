<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AfaOrderController extends Controller
{
    private float $paystackChargeRate = 0.025; // 2.5%

    public function show(string $slug): View
    {
        $afa = DB::table('afa_registrations')
            ->where('registration_slug', $slug)
            ->first();

        abort_if(!$afa, 404);

        if (strtolower($afa->status ?? '') !== 'active') {
            abort(404, 'This AFA registration is currently unavailable.');
        }

        $afa->image_url = $this->makeImageUrl($afa->registration_image ?? null);

        $paymentStatus = $this->paymentStatus();

        return view('users.afa.show', compact('afa', 'paymentStatus'));
    }

    public function initialize(Request $request, string $slug)
    {
        $user = Auth::guard('user')->user();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'ghana_card_number' => ['nullable', 'string', 'max:80'],
            'phone_number' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:150'],
            'occupation' => ['required', 'string', 'max:150'],
            'customer_email' => [$user ? 'nullable' : 'required', 'nullable', 'email', 'max:150'],
            'payment_method' => ['nullable', 'string', 'in:paystack_popup,wallet'],
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'paystack_popup';

        $paymentStatus = $this->paymentStatus($paymentMethod);

        if (!$paymentStatus['enabled']) {
            return response()->json([
                'status' => false,
                'message' => $paymentStatus['message'],
            ], 403);
        }

        if (!$user && $paymentMethod === 'wallet') {
            return response()->json([
                'status' => false,
                'message' => 'Wallet payment is only available for logged-in users.',
            ], 422);
        }

        $customerEmail = $user ? ($user->email ?? $request->customer_email) : $request->customer_email;

        if (!$customerEmail) {
            return response()->json([
                'status' => false,
                'message' => 'Customer email is required.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $afa = DB::table('afa_registrations')
                ->where('registration_slug', $slug)
                ->lockForUpdate()
                ->first();

            if (!$afa) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'AFA registration not found.',
                ], 404);
            }

            if (strtolower($afa->status ?? '') !== 'active') {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'This AFA registration is unavailable.',
                ], 422);
            }

            $baseAmount = (float) $afa->registration_amount;

            if ($baseAmount <= 0) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid AFA amount.',
                ], 422);
            }

            $charges = round($baseAmount * $this->paystackChargeRate, 2);
            $totalAmount = round($baseAmount + $charges, 2);
            $amountSubunit = (int) round($totalAmount * 100);

            $orderId = (string) Str::uuid();
            $paymentId = (string) Str::uuid();

            $orderReference = $this->uniqueShortReference('afa_orders', 'order_reference', 'afa-', 6);
            $paymentReference = $this->uniqueShortReference('afa_payments', 'payment_reference', '', 10);

            $customerName = strtoupper(trim($validated['customer_name']));
            $customerPhone = preg_replace('/\s+/', '', $validated['phone_number']);
            $customerLocation = strtoupper(trim($validated['location']));
            $customerOccupation = strtoupper(trim($validated['occupation']));
            $customerEmail = strtolower(trim($customerEmail));

            DB::table('afa_orders')->insert([
                'order_id' => $orderId,
                'user_id' => $user->user_id ?? null,
                'afa_registration_id' => $afa->afa_registration_id,
                'order_reference' => $orderReference,
                'registration_name' => $afa->registration_name,
                'amount' => $totalAmount,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'phone_number' => $customerPhone,
                'ghana_card_number' => $validated['ghana_card_number'] ?? null,
                'location' => $customerLocation,
                'occupation' => $customerOccupation,
                'status' => $paymentMethod === 'wallet' ? 'processing' : 'pending',
                'ordered_at' => now(),
                'processing_at' => $paymentMethod === 'wallet' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notes = [
                'type' => 'afa_registration',
                'order_reference' => $orderReference,
                'registration_name' => $afa->registration_name,
                'customer_name' => $customerName,
                'phone_number' => $customerPhone,
                'ghana_card_number' => $validated['ghana_card_number'] ?? null,
                'location' => $customerLocation,
                'occupation' => $customerOccupation,
                'base_amount' => $baseAmount,
                'charge_rate' => '2.5%',
                'charges' => $charges,
                'total_amount' => $totalAmount,
            ];

            if ($paymentMethod === 'wallet') {
                $freshUser = DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->lockForUpdate()
                    ->first();

                $walletBalance = (float) ($freshUser->wallet_balance ?? 0);

                if ($walletBalance < $totalAmount) {
                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient wallet balance. Required amount is GHS ' . number_format($totalAmount, 2),
                    ], 422);
                }

                $newWalletBalance = round($walletBalance - $totalAmount, 2);

                DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->update([
                        'wallet_balance' => $newWalletBalance,
                        'updated_at' => now(),
                    ]);

                DB::table('afa_payments')->insert([
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'user_id' => $user->user_id,
                    'payment_reference' => $paymentReference,
                    'payment_method' => 'wallet',
                    'amount' => $totalAmount,
                    'amount_subunit' => $amountSubunit,
                    'currency' => config('paystack.currency', 'GHS'),
                    'status' => 'successful',
                    'provider' => 'wallet',
                    'customer_email' => $customerEmail,
                    'provider_reference' => $paymentReference,
                    'gateway_response' => 'Wallet payment successful',
                    'channel' => 'wallet',
                    'fees' => $charges,
                    'notes' => json_encode($notes),
                    'paid_at' => now(),
                    'verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'payment_method' => 'wallet',
                    'message' => 'Wallet payment successful. Your AFA registration is processing.',
                    'redirect_url' => route('user.dashboard'),
                    'order_reference' => $orderReference,
                    'payment_reference' => $paymentReference,
                    'base_amount' => $baseAmount,
                    'charges' => $charges,
                    'total_amount' => $totalAmount,
                    'wallet_balance' => $newWalletBalance,
                ]);
            }

            DB::table('afa_payments')->insert([
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'user_id' => $user->user_id ?? null,
                'payment_reference' => $paymentReference,
                'payment_method' => 'paystack_popup',
                'amount' => $totalAmount,
                'amount_subunit' => $amountSubunit,
                'currency' => config('paystack.currency', 'GHS'),
                'status' => 'pending',
                'provider' => 'paystack',
                'customer_email' => $customerEmail,
                'fees' => $charges,
                'notes' => json_encode($notes),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = Http::withToken(config('paystack.secret_key'))
                ->acceptJson()
                ->timeout(25)
                ->post(rtrim(config('paystack.payment_url'), '/') . '/transaction/initialize', [
                    'email' => $customerEmail,
                    'amount' => $amountSubunit,
                    'currency' => config('paystack.currency', 'GHS'),
                    'reference' => $paymentReference,
                    'channels' => ['card', 'mobile_money'],
                    'metadata' => [
                        'type' => 'afa_registration',
                        'order_id' => $orderId,
                        'payment_id' => $paymentId,
                        'order_reference' => $orderReference,
                        'payment_reference' => $paymentReference,
                        'base_amount' => $baseAmount,
                        'charges' => $charges,
                        'total_amount' => $totalAmount,
                    ],
                ]);

            $result = $response->json();

            if (!$response->successful() || !($result['status'] ?? false)) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => $result['message'] ?? 'Payment initialization failed.',
                ], 422);
            }

            DB::table('afa_payments')
                ->where('payment_id', $paymentId)
                ->update([
                    'status' => 'initialized',
                    'access_code' => $result['data']['access_code'] ?? null,
                    'authorization_url' => $result['data']['authorization_url'] ?? null,
                    'api_response' => json_encode($result),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'payment_method' => 'paystack_popup',
                'message' => 'Payment initialized successfully.',
                'order_reference' => $orderReference,
                'payment_reference' => $paymentReference,
                'access_code' => $result['data']['access_code'] ?? null,
                'public_key' => config('paystack.public_key'),
                'customer_email' => $customerEmail,
                'amount_subunit' => $amountSubunit,
                'currency' => config('paystack.currency', 'GHS'),
                'base_amount' => $baseAmount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:100'],
        ]);

        $verified = $this->verifyPaymentReference($request->reference);

        return response()->json([
            'status' => $verified,
            'message' => $verified
                ? 'AFA payment successful. Your registration is processing.'
                : 'Payment could not be verified.',
            'redirect_url' => route('user.dashboard'),
        ], $verified ? 200 : 422);
    }

    public function verifyPaymentReference(string $reference): bool
    {
        $payment = DB::table('afa_payments')
            ->where('payment_reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (!$payment) {
            return false;
        }

        if ($payment->status === 'successful') {
            return true;
        }

        if ($payment->provider === 'wallet') {
            return $payment->status === 'successful';
        }

        $paymentStatus = $this->paymentStatus('paystack_popup');

        if (!$paymentStatus['enabled']) {
            return false;
        }

        $response = Http::withToken(config('paystack.secret_key'))
            ->acceptJson()
            ->timeout(25)
            ->get(rtrim(config('paystack.payment_url'), '/') . '/transaction/verify/' . urlencode($reference));

        $result = $response->json();

        if (!$response->successful() || !($result['status'] ?? false)) {
            DB::table('afa_payments')
                ->where('payment_id', $payment->payment_id)
                ->update([
                    'status' => 'failed',
                    'api_response' => json_encode($result),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            return false;
        }

        $data = $result['data'] ?? [];

        if (strtolower($data['status'] ?? '') !== 'success') {
            DB::table('afa_payments')
                ->where('payment_id', $payment->payment_id)
                ->update([
                    'status' => 'failed',
                    'gateway_response' => $data['gateway_response'] ?? null,
                    'api_response' => json_encode($result),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('afa_orders')
                ->where('order_id', $payment->order_id)
                ->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);

            return false;
        }

        $paidAmount = (int) ($data['amount'] ?? 0);

        if ($paidAmount !== (int) $payment->amount_subunit) {
            DB::table('afa_payments')
                ->where('payment_id', $payment->payment_id)
                ->update([
                    'status' => 'failed',
                    'gateway_response' => 'Amount mismatch',
                    'api_response' => json_encode($result),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            return false;
        }

        DB::transaction(function () use ($payment, $data, $result) {
            $freshPayment = DB::table('afa_payments')
                ->where('payment_id', $payment->payment_id)
                ->lockForUpdate()
                ->first();

            if (!$freshPayment || $freshPayment->status === 'successful') {
                return;
            }

            DB::table('afa_payments')
                ->where('payment_id', $freshPayment->payment_id)
                ->update([
                    'status' => 'successful',
                    'provider_reference' => $data['reference'] ?? $freshPayment->payment_reference,
                    'provider_transaction_id' => $data['id'] ?? null,
                    'gateway_response' => $data['gateway_response'] ?? null,
                    'channel' => $data['channel'] ?? null,
                    'fees' => isset($data['fees']) ? ((float) $data['fees'] / 100) : $freshPayment->fees,
                    'api_response' => json_encode($result),
                    'paid_at' => now(),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('afa_orders')
                ->where('order_id', $freshPayment->order_id)
                ->whereIn('status', ['pending', 'failed'])
                ->update([
                    'status' => 'processing',
                    'processing_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return true;
    }

    private function paymentStatus(string $paymentMethod = 'paystack_popup'): array
    {
        $setting = DB::table('business_settings')->first();

        $paymentSystemEnabled = (int) ($setting->payment_system_enabled ?? 0) === 1;
        $paystackEnabled = (int) ($setting->paystack_enabled ?? 0) === 1;

        $notice = trim((string) ($setting->payment_notice ?? ''));

        if (!$paymentSystemEnabled) {
            return [
                'enabled' => false,
                'title' => 'Payment System Disabled',
                'message' => $notice ?: 'Payment is currently unavailable. Please try again later.',
            ];
        }

        if ($paymentMethod === 'paystack_popup') {
            $paystackConfigured =
                filled(config('paystack.public_key')) &&
                filled(config('paystack.secret_key')) &&
                filled(config('paystack.payment_url'));

            if (!$paystackEnabled) {
                return [
                    'enabled' => false,
                    'title' => 'Paystack Disabled',
                    'message' => $notice ?: 'Paystack payment is currently unavailable. Please use wallet payment or try again later.',
                ];
            }

            if (!$paystackConfigured) {
                return [
                    'enabled' => false,
                    'title' => 'Payment Gateway Not Configured',
                    'message' => 'Paystack payment is temporarily unavailable because the payment gateway is not fully configured.',
                ];
            }
        }

        return [
            'enabled' => true,
            'title' => 'Payment Available',
            'message' => 'Payment is available.',
        ];
    }

    private function uniqueShortReference(string $table, string $column, string $prefix = '', int $length = 10): string
    {
        do {
            $reference = strtolower($prefix . Str::random($length));
        } while (DB::table($table)->where($column, $reference)->exists());

        return $reference;
    }

    private function makeImageUrl(?string $path): string
    {
        if (!$path) {
            return asset('images/afa-placeholder.png');
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}