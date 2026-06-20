<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WalletTopupController extends Controller
{
    private float $paystackChargeRate = 0.025; // 2.5%

    public function index(): View
    {
        $user = Auth::guard('user')->user();

        $paymentStatus = $this->paymentStatus();

        $topups = DB::table('wallet_topups')
            ->where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('users.wallet.index', compact(
            'user',
            'topups',
            'paymentStatus'
        ));
    }

    public function initialize(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Please login to top up your wallet.',
            ], 401);
        }

        $paymentStatus = $this->paymentStatus();

        if (!$paymentStatus['enabled']) {
            return response()->json([
                'status' => false,
                'message' => $paymentStatus['message'],
            ], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:50000'],
        ]);

        $amount = round((float) $validated['amount'], 2);

        if ($amount <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter a valid top up amount.',
            ], 422);
        }

        $charges = round($amount * $this->paystackChargeRate, 2);
        $totalAmount = round($amount + $charges, 2);
        $amountSubunit = (int) round($totalAmount * 100);

        $topupId = (string) Str::uuid();
        $topupReference = $this->uniqueShortReference('wallet_topups', 'topup_reference', 'top-', 8);
        $paymentReference = $this->uniqueShortReference('wallet_topups', 'payment_reference', '', 10);

        DB::beginTransaction();

        try {
            DB::table('wallet_topups')->insert([
                'topup_id' => $topupId,
                'user_id' => $user->user_id,
                'topup_reference' => $topupReference,
                'payment_reference' => $paymentReference,
                'amount' => $amount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
                'amount_subunit' => $amountSubunit,
                'currency' => config('paystack.currency', 'GHS'),
                'payment_method' => 'paystack_popup',
                'provider' => 'paystack',
                'status' => 'pending',
                'customer_email' => strtolower(trim($user->email)),
                'metadata' => json_encode([
                    'type' => 'wallet_topup',
                    'user_id' => $user->user_id,
                    'topup_reference' => $topupReference,
                    'payment_reference' => $paymentReference,
                    'wallet_credit_amount' => $amount,
                    'processing_charge' => $charges,
                    'total_payable' => $totalAmount,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = Http::withToken(config('paystack.secret_key'))
                ->acceptJson()
                ->timeout(25)
                ->post(rtrim(config('paystack.payment_url'), '/') . '/transaction/initialize', [
                    'email' => strtolower(trim($user->email)),
                    'amount' => $amountSubunit,
                    'currency' => config('paystack.currency', 'GHS'),
                    'reference' => $paymentReference,
                    'channels' => ['card', 'mobile_money'],
                    'metadata' => [
                        'type' => 'wallet_topup',
                        'user_id' => $user->user_id,
                        'topup_id' => $topupId,
                        'topup_reference' => $topupReference,
                        'payment_reference' => $paymentReference,
                        'wallet_credit_amount' => $amount,
                        'processing_charge' => $charges,
                        'total_payable' => $totalAmount,
                    ],
                ]);

            $result = $response->json();

            if (!$response->successful() || !($result['status'] ?? false)) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => $result['message'] ?? 'Wallet top up initialization failed.',
                ], 422);
            }

            DB::table('wallet_topups')
                ->where('topup_id', $topupId)
                ->update([
                    'status' => 'initialized',
                    'access_code' => $result['data']['access_code'] ?? null,
                    'authorization_url' => $result['data']['authorization_url'] ?? null,
                    'api_response' => json_encode($result),
                    'initialized_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Wallet top up initialized successfully.',
                'topup_reference' => $topupReference,
                'payment_reference' => $paymentReference,
                'access_code' => $result['data']['access_code'] ?? null,
                'public_key' => config('paystack.public_key'),
                'customer_email' => strtolower(trim($user->email)),
                'amount' => $amount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
                'amount_subunit' => $amountSubunit,
                'currency' => config('paystack.currency', 'GHS'),
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
                ? 'Wallet top up successful.'
                : 'Wallet top up could not be verified.',
            'redirect_url' => route('user.wallet.index'),
        ], $verified ? 200 : 422);
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()
                ->route('user.wallet.index')
                ->with('error', 'Payment reference missing.');
        }

        $verified = $this->verifyPaymentReference($reference);

        return redirect()
            ->route('user.wallet.index')
            ->with(
                $verified ? 'success' : 'error',
                $verified ? 'Wallet top up successful.' : 'Wallet top up verification failed.'
            );
    }

    public function verifyPaymentReference(string $reference): bool
    {
        $topup = DB::table('wallet_topups')
            ->where('payment_reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (!$topup) {
            return false;
        }

        if ($topup->status === 'successful') {
            return true;
        }

        $response = Http::withToken(config('paystack.secret_key'))
            ->acceptJson()
            ->timeout(25)
            ->get(rtrim(config('paystack.payment_url'), '/') . '/transaction/verify/' . urlencode($reference));

        $result = $response->json();

        if (!$response->successful() || !($result['status'] ?? false)) {
            DB::table('wallet_topups')
                ->where('topup_id', $topup->topup_id)
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
            DB::table('wallet_topups')
                ->where('topup_id', $topup->topup_id)
                ->update([
                    'status' => 'failed',
                    'gateway_response' => $data['gateway_response'] ?? null,
                    'api_response' => json_encode($result),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            return false;
        }

        $paidAmount = (int) ($data['amount'] ?? 0);

        if ($paidAmount !== (int) $topup->amount_subunit) {
            DB::table('wallet_topups')
                ->where('topup_id', $topup->topup_id)
                ->update([
                    'status' => 'failed',
                    'gateway_response' => 'Amount mismatch',
                    'api_response' => json_encode($result),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            return false;
        }

        DB::transaction(function () use ($topup, $data, $result) {
            $freshTopup = DB::table('wallet_topups')
                ->where('topup_id', $topup->topup_id)
                ->lockForUpdate()
                ->first();

            if (!$freshTopup || $freshTopup->status === 'successful') {
                return;
            }

            $freshUser = DB::table('users')
                ->where('user_id', $freshTopup->user_id)
                ->lockForUpdate()
                ->first();

            if (!$freshUser) {
                DB::table('wallet_topups')
                    ->where('topup_id', $freshTopup->topup_id)
                    ->update([
                        'status' => 'failed',
                        'gateway_response' => 'User account not found',
                        'api_response' => json_encode($result),
                        'verified_at' => now(),
                        'updated_at' => now(),
                    ]);

                return;
            }

            $balanceBefore = round((float) ($freshUser->wallet_balance ?? 0), 2);
            $creditAmount = round((float) $freshTopup->amount, 2);
            $balanceAfter = round($balanceBefore + $creditAmount, 2);

            DB::table('users')
                ->where('user_id', $freshUser->user_id)
                ->update([
                    'wallet_balance' => $balanceAfter,
                    'updated_at' => now(),
                ]);

            DB::table('wallet_topups')
                ->where('topup_id', $freshTopup->topup_id)
                ->update([
                    'status' => 'successful',
                    'provider_reference' => $data['reference'] ?? $freshTopup->payment_reference,
                    'provider_transaction_id' => $data['id'] ?? null,
                    'gateway_response' => $data['gateway_response'] ?? null,
                    'channel' => $data['channel'] ?? null,
                    'fees' => isset($data['fees']) ? ((float) $data['fees'] / 100) : null,
                    'api_response' => json_encode($result),
                    'paid_at' => now(),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('wallet_transactions')->insert([
                'wallet_transaction_id' => (string) Str::uuid(),
                'user_id' => $freshUser->user_id,
                'transaction_reference' => $this->uniqueShortReference('wallet_transactions', 'transaction_reference', 'txn-', 8),
                'transaction_type' => 'credit',
                'source' => 'wallet_topup',
                'source_id' => $freshTopup->topup_id,
                'amount' => $creditAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Wallet top up via Paystack',
                'status' => 'successful',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return true;
    }

    private function paymentStatus(): array
    {
        $setting = DB::table('business_settings')->first();

        $paymentSystemEnabled = (bool) ($setting->payment_system_enabled ?? false);
        $paystackEnabled = (bool) ($setting->paystack_enabled ?? false);

        $paystackConfigured =
            filled(config('paystack.public_key')) &&
            filled(config('paystack.secret_key')) &&
            filled(config('paystack.payment_url'));

        $notice = trim((string) ($setting->payment_notice ?? ''));

        if (!$paymentSystemEnabled) {
            return [
                'enabled' => false,
                'title' => 'Payment System Disabled',
                'message' => $notice ?: 'Wallet top up is currently unavailable. Please try again later.',
            ];
        }

        if (!$paystackEnabled) {
            return [
                'enabled' => false,
                'title' => 'Paystack Disabled',
                'message' => $notice ?: 'Paystack payment is currently unavailable. Please try again later.',
            ];
        }

        if (!$paystackConfigured) {
            return [
                'enabled' => false,
                'title' => 'Payment Gateway Not Configured',
                'message' => 'Wallet top up is temporarily unavailable because the payment gateway is not fully configured.',
            ];
        }

        return [
            'enabled' => true,
            'title' => 'Payment Available',
            'message' => 'Wallet top up is available.',
        ];
    }

    private function uniqueShortReference(string $table, string $column, string $prefix = '', int $length = 10): string
    {
        do {
            $reference = strtolower($prefix . Str::random($length));
        } while (DB::table($table)->where($column, $reference)->exists());

        return $reference;
    }

    public function resume(Request $request)
{
    $user = Auth::guard('user')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Please login to continue payment.',
        ], 401);
    }

    $paymentStatus = $this->paymentStatus();

    if (!$paymentStatus['enabled']) {
        return response()->json([
            'status' => false,
            'message' => $paymentStatus['message'],
        ], 403);
    }

    $validated = $request->validate([
        'topup_id' => ['required', 'string'],
    ]);

    $topup = DB::table('wallet_topups')
        ->where('topup_id', $validated['topup_id'])
        ->where('user_id', $user->user_id)
        ->first();

    if (!$topup) {
        return response()->json([
            'status' => false,
            'message' => 'Top up record not found.',
        ], 404);
    }

    if ($topup->status === 'successful') {
        return response()->json([
            'status' => false,
            'message' => 'This wallet top up has already been paid.',
        ], 422);
    }

    if (empty($topup->access_code)) {
        return response()->json([
            'status' => false,
            'message' => 'Payment session is not available. Please create a new top up.',
        ], 422);
    }

    return response()->json([
        'status' => true,
        'message' => 'Continue your wallet top up payment.',
        'topup_id' => $topup->topup_id,
        'topup_reference' => $topup->topup_reference,
        'payment_reference' => $topup->payment_reference,
        'access_code' => $topup->access_code,
        'public_key' => config('paystack.public_key'),
        'customer_email' => $topup->customer_email,
        'amount' => (float) $topup->amount,
        'charges' => (float) $topup->charges,
        'total_amount' => (float) $topup->total_amount,
        'amount_subunit' => (int) $topup->amount_subunit,
        'currency' => $topup->currency ?? config('paystack.currency', 'GHS'),
    ]);
}

}