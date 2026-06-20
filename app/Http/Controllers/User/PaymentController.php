<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private float $paystackChargeRate = 0.025;

    public function initialize(Request $request)
    {
        Log::info('[PAYSTACK INIT] Request received', [
            'payload' => $request->except(['card', 'authorization']),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $validated = $request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,plan_id'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'network_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'is_ported' => ['nullable'],
        ]);

        $user = Auth::guard('user')->user();

        if (! $user && empty($validated['email'])) {
            return response()->json([
                'status' => false,
                'message' => 'Email address is required for payment.',
            ], 422);
        }

        $secretKey = $this->paystackSecretKey();
        $publicKey = $this->paystackPublicKey();

        if (empty($secretKey) || empty($publicKey)) {
            Log::error('[PAYSTACK INIT] Paystack keys missing.');

            return response()->json([
                'status' => false,
                'message' => 'Paystack is not configured. Please contact support.',
            ], 500);
        }

        DB::beginTransaction();

        try {
            $plan = $this->getLockedPlan($validated['plan_id']);

            if (! $plan) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Selected package was not found.',
                ], 404);
            }

            $planCheck = $this->validatePlanStatus($plan);

            if ($planCheck !== true) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => $planCheck,
                ], 422);
            }

            $beneficiaryNumber = $this->normalizePhone($validated['recipient_phone']);

            if (! $beneficiaryNumber) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid beneficiary phone number.',
                ], 422);
            }

            $baseAmount = round((float) $plan->selling_price, 2);

            if ($baseAmount <= 0) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid package amount.',
                ], 422);
            }

            $charges = round($baseAmount * $this->paystackChargeRate, 2);
            $totalAmount = round($baseAmount + $charges, 2);
            $amountSubunit = (int) round($totalAmount * 100);

            $orderId = (string) Str::uuid();
            $orderReference = $this->generateShortOrderNumber();
            $paymentId = (string) Str::uuid();
            $paymentReference = $this->generatePaymentReference();

            $customerEmail = $user
                ? ($user->email ?? $validated['email'])
                : $validated['email'];

            $network = strtoupper($plan->network ?: ($validated['network_name'] ?? $plan->category_name ?? 'DATA'));
            $dataSize = strtoupper($plan->data_volume ?? $plan->plan_name);
            $planName = strtoupper($plan->plan_name ?? $dataSize);

            $note = $this->buildOrderMessage($dataSize, $beneficiaryNumber);

            DB::table('orders')->insert($this->onlyExistingColumns('orders', [
                'order_id' => $orderId,
                'user_id' => $user->user_id ?? null,
                'plan_id' => $plan->plan_id,
                'category_id' => $plan->category_id,
                'order_reference' => $orderReference,
                'network_name' => $network,
                'plan_name' => $planName,
                'data_size' => $dataSize,
                'beneficiary_number' => $beneficiaryNumber,
                'customer_email' => $customerEmail,
                'amount' => $baseAmount,
                'is_ported' => $request->boolean('is_ported') ? 1 : 0,
                'status' => 'pending',
                'ordered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            DB::table('payments')->insert($this->onlyExistingColumns('payments', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'user_id' => $user->user_id ?? null,
                'payment_reference' => $paymentReference,
                'payment_method' => 'paystack',
                'amount' => $totalAmount,
                'charges' => $charges,
                'amount_subunit' => $amountSubunit,
                'currency' => $this->paystackCurrency(),
                'status' => 'pending',
                'provider' => 'paystack',
                'provider_reference' => $paymentReference,
                'provider_transaction_id' => null,
                'api_response' => null,
                'access_code' => null,
                'authorization_url' => null,
                'gateway_response' => null,
                'channel' => null,
                'fees' => null,
                'customer_email' => $customerEmail,
                'notes' => $note . " Package amount: GHS {$baseAmount}. Paystack charge: GHS {$charges}. Total: GHS {$totalAmount}.",
                'paid_at' => null,
                'verified_at' => null,
                'webhook_received_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $paystackPayload = [
                'email' => $customerEmail,
                'amount' => $amountSubunit,
                'currency' => $this->paystackCurrency(),
                'reference' => $paymentReference,
                'callback_url' => route('user.payments.callback'),
                'channels' => ['card', 'bank', 'ussd', 'mobile_money'],
                'metadata' => [
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'order_reference' => $orderReference,
                    'user_id' => $user->user_id ?? null,
                    'network' => $network,
                    'data_size' => $dataSize,
                    'beneficiary_number' => $beneficiaryNumber,
                    'package_amount' => $baseAmount,
                    'paystack_charge' => $charges,
                    'total_amount' => $totalAmount,
                    'security_note' => 'Amount generated from server only',
                ],
            ];

            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(30)
                ->post($this->paystackBaseUrl() . '/transaction/initialize', $paystackPayload);

            Log::info('[PAYSTACK INIT] Paystack response received', [
                'successful' => $response->successful(),
                'status' => $response->json('status'),
                'message' => $response->json('message'),
                'reference' => $paymentReference,
            ]);

            if (! $response->successful() || $response->json('status') !== true) {
                DB::rollBack();

                Log::error('[PAYSTACK INIT] Paystack failed', [
                    'response' => $response->json(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => $response->json('message') ?: 'Unable to initialize payment.',
                ], 500);
            }

            $paystackData = $response->json('data');

            DB::table('payments')
                ->where('payment_id', $paymentId)
                ->update($this->onlyExistingColumns('payments', [
                    'status' => 'initialized',
                    'access_code' => $paystackData['access_code'] ?? null,
                    'authorization_url' => $paystackData['authorization_url'] ?? null,
                    'api_response' => json_encode($response->json()),
                    'updated_at' => now(),
                ]));

            DB::commit();

            Log::info('[PAYSTACK INIT] Completed successfully', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'order_reference' => $orderReference,
                'payment_reference' => $paymentReference,
                'base_amount' => $baseAmount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Payment initialized successfully.',
                'public_key' => $publicKey,
                'email' => $customerEmail,
                'amount' => $amountSubunit,
                'base_amount' => $baseAmount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
                'currency' => $this->paystackCurrency(),
                'reference' => $paymentReference,
                'order_id' => $orderId,
                'order_reference' => $orderReference,
                'access_code' => $paystackData['access_code'] ?? null,
                'authorization_url' => $paystackData['authorization_url'] ?? null,
                'toast' => [
                    'title' => 'Order Received',
                    'message' => "Your {$dataSize} to {$beneficiaryNumber} has been accepted and is processing.",
                    'delivery' => 'Delivery: 30-60 mins',
                    'buy_again' => config('app.url'),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('[PAYSTACK INIT] Exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'payload' => $request->except(['card', 'authorization']),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Payment initialization failed.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function payWithWallet(Request $request)
    {
        Log::info('[WALLET PAYMENT] Request received', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        $validated = $request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,plan_id'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'network_name' => ['nullable', 'string', 'max:100'],
            'is_ported' => ['nullable'],
        ]);

        $authUser = Auth::guard('user')->user();

        if (! $authUser) {
            return response()->json([
                'status' => false,
                'message' => 'You must be logged in to make payment.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $user = DB::table('users')
                ->where('user_id', $authUser->user_id)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'User account not found.',
                ], 404);
            }

            $plan = $this->getLockedPlan($validated['plan_id']);

            if (! $plan) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Selected package was not found.',
                ], 404);
            }

            $planCheck = $this->validatePlanStatus($plan);

            if ($planCheck !== true) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => $planCheck,
                ], 422);
            }

            $beneficiaryNumber = $this->normalizePhone($validated['recipient_phone']);

            if (! $beneficiaryNumber) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid beneficiary phone number.',
                ], 422);
            }

            $amount = round((float) $plan->selling_price, 2);
            $walletBalance = round((float) ($user->wallet_balance ?? 0), 2);

            if ($amount <= 0) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid package amount.',
                ], 422);
            }

            if ($walletBalance < $amount) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient wallet balance.',
                    'wallet_balance' => number_format($walletBalance, 2),
                    'order_amount' => number_format($amount, 2),
                ], 422);
            }

            $orderId = (string) Str::uuid();
            $orderReference = $this->generateShortOrderNumber();
            $paymentId = (string) Str::uuid();
            $paymentReference = $this->generatePaymentReference();

            $network = strtoupper($plan->network ?: ($validated['network_name'] ?? $plan->category_name ?? 'DATA'));
            $dataSize = strtoupper($plan->data_volume ?? $plan->plan_name);
            $planName = strtoupper($plan->plan_name ?? $dataSize);
            $balanceAfter = round($walletBalance - $amount, 2);
            $note = $this->buildOrderMessage($dataSize, $beneficiaryNumber);

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update([
                    'wallet_balance' => $balanceAfter,
                    'updated_at' => now(),
                ]);

            DB::table('orders')->insert($this->onlyExistingColumns('orders', [
                'order_id' => $orderId,
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'category_id' => $plan->category_id,
                'order_reference' => $orderReference,
                'network_name' => $network,
                'plan_name' => $planName,
                'data_size' => $dataSize,
                'beneficiary_number' => $beneficiaryNumber,
                'customer_email' => $user->email,
                'amount' => $amount,
                'is_ported' => $request->boolean('is_ported') ? 1 : 0,
                'status' => 'processing',
                'ordered_at' => now(),
                'processing_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            DB::table('payments')->insert($this->onlyExistingColumns('payments', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'user_id' => $user->user_id,
                'payment_reference' => $paymentReference,
                'payment_method' => 'wallet',
                'amount' => $amount,
                'charges' => 0,
                'amount_subunit' => (int) round($amount * 100),
                'currency' => $this->paystackCurrency(),
                'status' => 'successful',
                'provider' => 'wallet',
                'provider_reference' => $paymentReference,
                'provider_transaction_id' => $paymentReference,
                'api_response' => json_encode([
                    'wallet_balance_before' => $walletBalance,
                    'wallet_balance_after' => $balanceAfter,
                ]),
                'access_code' => null,
                'authorization_url' => null,
                'gateway_response' => 'Paid successfully using wallet balance',
                'channel' => 'wallet',
                'fees' => 0,
                'customer_email' => $user->email,
                'notes' => $note . " Paid with wallet. Amount: GHS {$amount}.",
                'paid_at' => now(),
                'verified_at' => now(),
                'webhook_received_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

if (Schema::hasTable('wallet_transactions')) {
    DB::table('wallet_transactions')->insert($this->onlyExistingColumns('wallet_transactions', [
        'wallet_transaction_id' => (string) Str::uuid(),
        'user_id' => $user->user_id,
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'transaction_reference' => $paymentReference,

        'transaction_type' => 'debit',
        'source' => 'wallet_payment',

        'amount' => $amount,
        'balance_before' => $walletBalance,
        'balance_after' => $balanceAfter,
        'description' => $note,
        'status' => 'successful',

        'created_at' => now(),
        'updated_at' => now(),
    ]));
}

            DB::commit();

            $freshOrder = DB::table('orders')->where('order_id', $orderId)->first();
            $freshPayment = DB::table('payments')->where('payment_id', $paymentId)->first();

            $this->sendOrderProcessingNotification($user, $freshOrder, $freshPayment, 'wallet');

            Log::info('[WALLET PAYMENT] Completed successfully', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'order_reference' => $orderReference,
                'payment_reference' => $paymentReference,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Wallet payment successful. Your order is processing.',
                'wallet_balance' => number_format($balanceAfter, 2),
                'order_id' => $orderId,
                'order_reference' => $orderReference,
                'reference' => $paymentReference,
                'toast' => [
                    'title' => 'Order Received',
                    'message' => "Your {$dataSize} to {$beneficiaryNumber} has been accepted and is processing.",
                    'delivery' => 'Delivery: 30-60 mins',
                    'buy_again' => config('app.url'),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('[WALLET PAYMENT] Exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Wallet payment failed.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        Log::info('[PAYSTACK CALLBACK] Received', [
            'reference' => $reference,
            'query' => $request->query(),
        ]);

        if (! $reference) {
            return redirect('/dashboard')->with('error', 'Invalid payment reference.');
        }

        return $this->verifyPayment($reference, true);
    }

    public function verifyFromPopup(Request $request)
    {
        $request->validate([
            'reference' => ['required', 'string'],
        ]);

        Log::info('[PAYSTACK POPUP VERIFY] Received', [
            'reference' => $request->reference,
        ]);

        $verified = $this->verifyPayment($request->reference, false);

        if (! $verified) {
            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed.',
            ], 422);
        }

        $payment = DB::table('payments')
            ->where('payment_reference', $request->reference)
            ->first();

        $order = $payment
            ? DB::table('orders')->where('order_id', $payment->order_id)->first()
            : null;

        return response()->json([
            'status' => true,
            'message' => 'Payment successful. Your order is processing.',
            'toast' => [
                'title' => 'Order Received',
                'message' => $order
                    ? "Your {$order->data_size} to {$order->beneficiary_number} has been accepted and is processing."
                    : 'Your order has been accepted and is processing.',
                'delivery' => 'Delivery: 30-60 mins',
                'buy_again' => config('app.url'),
            ],
        ]);
    }

    public function verifyPaymentFromWebhook(string $reference): bool
    {
        Log::info('[PAYSTACK WEBHOOK VERIFY] Received', [
            'reference' => $reference,
        ]);

        return $this->verifyPayment($reference, false, true);
    }

    public function verifyPayment(string $reference, bool $redirect = false, bool $fromWebhook = false)
    {
        Log::info('[PAYSTACK VERIFY] Started', [
            'reference' => $reference,
            'redirect' => $redirect,
            'from_webhook' => $fromWebhook,
        ]);

        $secretKey = $this->paystackSecretKey();

        if (empty($secretKey)) {
            return $this->paymentReturn($redirect, false, 'Paystack is not configured.');
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->timeout(30)
            ->get($this->paystackBaseUrl() . '/transaction/verify/' . urlencode($reference));

        Log::info('[PAYSTACK VERIFY] Paystack response received', [
            'reference' => $reference,
            'successful' => $response->successful(),
            'status' => $response->json('status'),
            'message' => $response->json('message'),
        ]);

        if (! $response->successful() || $response->json('status') !== true) {
            Log::warning('[PAYSTACK VERIFY] Paystack request failed', [
                'reference' => $reference,
                'response' => $response->json(),
            ]);

            return $this->paymentReturn($redirect, false, 'Payment verification failed.');
        }

        $data = $response->json('data');

        DB::beginTransaction();

        try {
            $payment = DB::table('payments')
                ->where(function ($query) use ($reference) {
                    $query->where('payment_reference', $reference)
                        ->orWhere('provider_reference', $reference);
                })
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                DB::rollBack();

                return $this->paymentReturn($redirect, false, 'Payment record not found.');
            }

            if (($payment->status ?? '') === 'successful') {
                DB::rollBack();

                return $this->paymentReturn($redirect, true, 'Payment already confirmed.');
            }

            $order = DB::table('orders')
                ->where('order_id', $payment->order_id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                DB::rollBack();

                return $this->paymentReturn($redirect, false, 'Order not found.');
            }

            $user = $payment->user_id
                ? DB::table('users')->where('user_id', $payment->user_id)->first()
                : null;

            $paystackStatus = $data['status'] ?? null;
            $amountPaid = (int) ($data['amount'] ?? 0);
            $expectedAmount = (int) ($payment->amount_subunit ?? 0);
            $currency = strtoupper($data['currency'] ?? '');
            $expectedCurrency = strtoupper($payment->currency ?? $this->paystackCurrency());
            $paidEmail = strtolower($data['customer']['email'] ?? '');
            $expectedEmail = strtolower($payment->customer_email ?? $order->customer_email ?? '');
            $paystackReference = $data['reference'] ?? null;

            $isValid =
                $paystackStatus === 'success'
                && $amountPaid === $expectedAmount
                && $currency === $expectedCurrency
                && $paidEmail === $expectedEmail
                && $paystackReference === $payment->payment_reference;

            if (! $isValid) {
                DB::table('payments')
                    ->where('payment_id', $payment->payment_id)
                    ->update($this->onlyExistingColumns('payments', [
                        'status' => 'failed',
                        'gateway_response' => $data['gateway_response'] ?? 'Verification mismatch',
                        'api_response' => json_encode($response->json()),
                        'verified_at' => now(),
                        'updated_at' => now(),
                    ]));

                DB::commit();

                Log::warning('[PAYSTACK VERIFY] Payment mismatch', [
                    'reference' => $reference,
                    'paystack_status' => $paystackStatus,
                    'amount_paid' => $amountPaid,
                    'expected_amount' => $expectedAmount,
                    'currency' => $currency,
                    'expected_currency' => $expectedCurrency,
                    'paid_email' => $paidEmail,
                    'expected_email' => $expectedEmail,
                    'paystack_reference' => $paystackReference,
                    'payment_reference' => $payment->payment_reference,
                ]);

                return $this->paymentReturn($redirect, false, 'Payment failed or amount mismatch.');
            }

            DB::table('payments')
                ->where('payment_id', $payment->payment_id)
                ->update($this->onlyExistingColumns('payments', [
                    'status' => 'successful',
                    'payment_method' => $data['channel'] ?? 'paystack',
                    'provider' => 'paystack',
                    'provider_reference' => $data['reference'] ?? $payment->payment_reference,
                    'provider_transaction_id' => $data['id'] ?? null,
                    'gateway_response' => $data['gateway_response'] ?? null,
                    'channel' => $data['channel'] ?? null,
                    'fees' => isset($data['fees']) ? ((float) $data['fees'] / 100) : null,
                    'api_response' => json_encode($response->json()),
                    'paid_at' => now(),
                    'verified_at' => now(),
                    'webhook_received_at' => $fromWebhook ? now() : ($payment->webhook_received_at ?? null),
                    'updated_at' => now(),
                ]));

            DB::table('orders')
                ->where('order_id', $payment->order_id)
                ->update($this->onlyExistingColumns('orders', [
                    'status' => 'processing',
                    'processing_at' => now(),
                    'updated_at' => now(),
                ]));

            DB::commit();

            $freshOrder = DB::table('orders')->where('order_id', $payment->order_id)->first();
            $freshPayment = DB::table('payments')->where('payment_id', $payment->payment_id)->first();

            if ($user) {
                $this->sendOrderProcessingNotification($user, $freshOrder, $freshPayment, 'paystack');
            }

            Log::info('[PAYSTACK VERIFY] Completed successfully', [
                'payment_id' => $payment->payment_id,
                'order_id' => $payment->order_id,
                'reference' => $reference,
            ]);

            return $this->paymentReturn($redirect, true, 'Payment successful. Your order is processing.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('[PAYSTACK VERIFY] Exception', [
                'reference' => $reference,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return $this->paymentReturn($redirect, false, 'Payment verification failed.');
        }
    }

    private function sendOrderProcessingNotification($user, $order, $payment, string $method): void
    {
        if (! $user || ! $order || ! $payment) {
            Log::warning('[PAYMENT NOTIFICATION] Missing data', [
                'has_user' => (bool) $user,
                'has_order' => (bool) $order,
                'has_payment' => (bool) $payment,
            ]);

            return;
        }

        try {
            $name = strtoupper(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
            $name = $name ?: strtoupper($user->name ?? $user->email ?? 'CUSTOMER');

            $dataSize = strtoupper($order->data_size ?? 'DATA');
            $network = strtoupper($order->network_name ?? 'DATA');
            $beneficiary = $order->beneficiary_number ?? '';
            $packageAmount = number_format((float) ($order->amount ?? 0), 2);
            $totalPaid = number_format((float) ($payment->amount ?? 0), 2);
            $charge = number_format(max(((float) ($payment->amount ?? 0)) - ((float) ($order->amount ?? 0)), 0), 2);
            $reference = $payment->payment_reference;
            $orderReference = $order->order_reference;
            $date = now()->format('d M Y, h:i A');
            $methodText = strtoupper($method === 'wallet' ? 'Wallet Balance' : 'Paystack / MoMo');

            $sms = $this->buildOrderMessage($dataSize, $beneficiary);

            if (! empty($user->phone)) {
                $this->sendSms($user->phone, $sms);
            }

            if (! empty($user->email)) {
                $html = "
                    <div style='font-family:Arial,sans-serif;background:#f4f7fb;padding:24px;'>
                        <div style='max-width:650px;margin:auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;'>
                            <div style='background:#071f44;color:#ffffff;padding:24px;'>
                                <h2 style='margin:0;font-size:24px;'>Order Received</h2>
                                <p style='margin:8px 0 0;font-size:14px;'>Your data order has been accepted and is processing.</p>
                            </div>

                            <div style='padding:24px;color:#111827;'>
                                <p>Dear <strong>{$name}</strong>,</p>
                                <p>Your <strong>{$dataSize}</strong> data bundle to <strong>{$beneficiary}</strong> has been accepted and is now processing.</p>

                                <table style='width:100%;border-collapse:collapse;margin-top:18px;font-size:14px;'>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Order Number</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>{$orderReference}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Payment Reference</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>{$reference}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Network</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>{$network}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Data Size</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>{$dataSize}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Beneficiary Number</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>{$beneficiary}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Payment Method</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>{$methodText}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Package Amount</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>GH₵ {$packageAmount}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Processing Charge</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>GH₵ {$charge}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Total Paid</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>GH₵ {$totalPaid}</strong></td></tr>
                                    <tr><td style='padding:11px;border-bottom:1px solid #e5e7eb;'>Delivery</td><td style='padding:11px;border-bottom:1px solid #e5e7eb;text-align:right;'><strong>30-60 mins</strong></td></tr>
                                    <tr><td style='padding:11px;'>Date</td><td style='padding:11px;text-align:right;'><strong>{$date}</strong></td></tr>
                                </table>

                                <p style='margin-top:22px;'>Thank you for using KlikDataHub.</p>
                            </div>
                        </div>
                    </div>
                ";

                Mail::html($html, function ($message) use ($user) {
                    $message->to($user->email)->subject('Order Received - Data Purchase Processing');
                });

                Log::info('[PAYMENT NOTIFICATION] Email sent', [
                    'email' => $user->email,
                    'order_id' => $order->order_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[PAYMENT NOTIFICATION] Failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function sendSms(string $phone, string $message): void
    {
        try {
            $phone = preg_replace('/\D/', '', $phone);

            if (str_starts_with($phone, '0')) {
                $phone = '233' . substr($phone, 1);
            }

            if (empty(env('SMSNOTIFY_API_BASE')) || empty(env('SMSNOTIFY_API_KEY'))) {
                Log::warning('[PAYMENT SMS] SMS config missing.');
                return;
            }

            $response = Http::timeout(20)->get(env('SMSNOTIFY_API_BASE'), [
                'key' => env('SMSNOTIFY_API_KEY'),
                'to' => $phone,
                'msg' => $message,
                'sender_id' => env('SMS_SENDER_ID', 'KlikData'),
            ]);

            Log::info('[PAYMENT SMS] Request sent', [
                'phone' => $phone,
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PAYMENT SMS] Failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getLockedPlan(string $planId)
    {
        return DB::table('plans')
            ->leftJoin('categories', 'plans.category_id', '=', 'categories.category_id')
            ->select(
                'plans.plan_id',
                'plans.category_id',
                'plans.plan_name',
                'plans.network',
                'plans.data_volume',
                'plans.selling_price',
                'plans.status as plan_status',
                'categories.category_name',
                'categories.status as category_status'
            )
            ->where('plans.plan_id', $planId)
            ->lockForUpdate()
            ->first();
    }

    private function validatePlanStatus($plan)
    {
        if (strtolower((string) $plan->plan_status) !== 'active') {
            return 'This package is unavailable.';
        }

        if (strtolower((string) $plan->category_status) !== 'active') {
            return 'This network is currently out of stock.';
        }

        return true;
    }

    private function buildOrderMessage(string $dataSize, string $beneficiary): string
    {
        return "Order Received: Your {$dataSize} to {$beneficiary} has been accepted and is processing. Delivery: 30-60 mins. Buy again: " . config('app.url');
    }

    private function paymentReturn(bool $redirect, bool $success, string $message)
    {
        Log::info('[PAYMENT RETURN]', [
            'redirect' => $redirect,
            'success' => $success,
            'message' => $message,
        ]);

        if ($redirect) {
            return redirect('/dashboard')->with($success ? 'success' : 'error', $message);
        }

        return $success;
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

    private function generatePaymentReference(): string
    {
        do {
            $reference = strtolower(Str::random(10));
        } while (DB::table('payments')->where('payment_reference', $reference)->exists());

        return $reference;
    }

    private function generateShortOrderNumber(): string
    {
        do {
            $reference = strtoupper(Str::random(8));
        } while (DB::table('orders')->where('order_reference', $reference)->exists());

        return $reference;
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }

    private function paystackSecretKey(): ?string
    {
        return config('paystack.secret_key') ?: config('services.paystack.secret_key');
    }

    private function paystackPublicKey(): ?string
    {
        return config('paystack.public_key') ?: config('services.paystack.public_key');
    }

    private function paystackBaseUrl(): string
    {
        return rtrim(config('paystack.payment_url') ?: config('services.paystack.payment_url', 'https://api.paystack.co'), '/');
    }

    private function paystackCurrency(): string
    {
        return config('paystack.currency') ?: config('services.paystack.currency', 'GHS');
    }
}