<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VerifyPaymentController extends Controller
{
    public function index(Request $request)
    {
        $rawReferences = trim((string) $request->query('payment_references', ''));

        $payments = collect();

        if ($rawReferences !== '') {
            $references = $this->parseReferences($rawReferences);
            $payments = $this->findPaymentsByReferences($references);
        }

        return view('admin.finance.verify-payments', [
            'payments' => $payments,
            'references' => $rawReferences,
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'payment_references' => ['required', 'string', 'max:5000'],
        ]);

        return redirect()->route('admin.finance.verify-payments', [
            'payment_references' => $validated['payment_references'],
        ]);
    }

    public function verifyPaystack(Request $request)
    {
        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:150'],
            'payment_scope' => ['nullable', Rule::in(['data_order', 'afa_order', 'wallet_topup'])],
            'return_references' => ['nullable', 'string', 'max:5000'],
        ]);

        $reference = trim($validated['payment_reference']);
        $returnReferences = $validated['return_references'] ?? $reference;

        try {
            $result = $this->verifySinglePaystackReference($reference);

            return redirect()
                ->route('admin.finance.verify-payments', ['payment_references' => $returnReferences])
                ->with($result['status'], $result['message']);
        } catch (\Throwable $e) {
            Log::warning('Admin Paystack verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.finance.verify-payments', ['payment_references' => $returnReferences])
                ->with('error', $e->getMessage());
        }
    }

    public function verifyBulkPaystack(Request $request)
    {
        $validated = $request->validate([
            'payment_references' => ['required', 'array', 'min:1'],
            'payment_references.*' => ['required', 'string', 'max:150'],
            'return_references' => ['nullable', 'string', 'max:5000'],
        ]);

        $references = collect($validated['payment_references'])
            ->map(fn ($reference) => trim((string) $reference))
            ->filter()
            ->unique()
            ->values();

        $returnReferences = $validated['return_references'] ?? $references->implode("\n");

        $success = 0;
        $already = 0;
        $failed = 0;
        $messages = [];

        foreach ($references as $reference) {
            try {
                $result = $this->verifySinglePaystackReference($reference);

                if (($result['code'] ?? null) === 'verified') {
                    $success++;
                } elseif (($result['code'] ?? null) === 'already') {
                    $already++;
                } else {
                    $failed++;
                }

                $messages[] = $reference . ': ' . $result['message'];
            } catch (\Throwable $e) {
                $failed++;

                Log::warning('Bulk Paystack verification failed', [
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);

                $messages[] = $reference . ': ' . $e->getMessage();
            }
        }

        $message = "Bulk Paystack verification completed. Successful: {$success}. Already verified: {$already}. Failed: {$failed}.";

        if ($failed > 0) {
            $message .= ' Check logs for failed references.';
        }

        return redirect()
            ->route('admin.finance.verify-payments', ['payment_references' => $returnReferences])
            ->with($failed > 0 ? 'error' : 'success', $message);
    }

    public function verifyManual(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'string', 'max:150'],
            'payment_scope' => ['required', Rule::in(['data_order', 'afa_order', 'wallet_topup'])],
            'note' => ['nullable', 'string', 'max:500'],
            'return_references' => ['nullable', 'string', 'max:5000'],
        ]);

        $returnReferences = $validated['return_references'] ?? '';

        try {
            $result = $this->verifySingleManualPayment($validated);

            return redirect()
                ->route('admin.finance.verify-payments', ['payment_references' => $returnReferences])
                ->with($result['status'], $result['message']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.finance.verify-payments', ['payment_references' => $returnReferences])
                ->with('error', $e->getMessage());
        }
    }

    public function verifyBulkManual(Request $request)
    {
        $validated = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_id' => ['required', 'string', 'max:150'],
            'payments.*.payment_scope' => ['required', Rule::in(['data_order', 'afa_order', 'wallet_topup'])],
            'note' => ['nullable', 'string', 'max:500'],
            'return_references' => ['nullable', 'string', 'max:5000'],
        ]);

        $returnReferences = $validated['return_references'] ?? '';

        $success = 0;
        $already = 0;
        $failed = 0;

        foreach ($validated['payments'] as $payment) {
            try {
                $payload = [
                    'payment_id' => $payment['payment_id'],
                    'payment_scope' => $payment['payment_scope'],
                    'note' => $validated['note'] ?? null,
                ];

                $result = $this->verifySingleManualPayment($payload);

                if (($result['code'] ?? null) === 'verified') {
                    $success++;
                } elseif (($result['code'] ?? null) === 'already') {
                    $already++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;

                Log::warning('Bulk manual verification failed', [
                    'payment_id' => $payment['payment_id'] ?? null,
                    'payment_scope' => $payment['payment_scope'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "Bulk manual verification completed. Successful: {$success}. Already verified: {$already}. Failed: {$failed}.";

        return redirect()
            ->route('admin.finance.verify-payments', ['payment_references' => $returnReferences])
            ->with($failed > 0 ? 'error' : 'success', $message);
    }

    private function verifySinglePaystackReference(string $reference): array
    {
        $payment = $this->findPaymentByReference($reference);

        if (!$payment) {
            throw new \RuntimeException('No local payment found with this reference.');
        }

        if (($payment->status ?? null) === 'successful') {
            return [
                'status' => 'success',
                'code' => 'already',
                'message' => 'Payment is already verified.',
            ];
        }

        $payload = $this->verifyFromPaystack($reference);
        $data = $payload['data'] ?? [];

        if (strtolower((string) ($data['status'] ?? '')) !== 'success') {
            throw new \RuntimeException('Paystack transaction is not successful.');
        }

        DB::transaction(function () use ($payment, $reference, $payload, $data) {
            if ($payment->payment_scope === 'wallet_topup') {
                $this->completeWalletTopup($payment, $reference, $payload, $data);
                return;
            }

            if ($payment->payment_scope === 'afa_order') {
                $this->completeAfaPayment($payment, $reference, $payload, $data);
                return;
            }

            $this->completeNormalPayment($payment, $reference, $payload, $data);
        });

        return [
            'status' => 'success',
            'code' => 'verified',
            'message' => 'Payment verified successfully from Paystack.',
        ];
    }

    private function verifySingleManualPayment(array $validated): array
    {
        DB::transaction(function () use ($validated) {
            if ($validated['payment_scope'] === 'wallet_topup') {
                $this->manualWalletTopup($validated);
                return;
            }

            if ($validated['payment_scope'] === 'afa_order') {
                $this->manualAfaPayment($validated);
                return;
            }

            $this->manualNormalPayment($validated);
        });

        return [
            'status' => 'success',
            'code' => 'verified',
            'message' => 'Payment verified manually.',
        ];
    }

    private function parseReferences(string $raw): Collection
    {
        return collect(preg_split('/[\s,;]+/', $raw))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();
    }

    private function findPaymentsByReferences(Collection $references): Collection
    {
        if ($references->isEmpty()) {
            return collect();
        }

        $payments = collect();

        $normalPayments = DB::table('payments')
            ->leftJoin('orders', 'payments.order_id', '=', 'orders.order_id')
            ->where(function ($query) use ($references) {
                $query->whereIn('payments.payment_reference', $references)
                    ->orWhereIn('payments.provider_reference', $references)
                    ->orWhereIn('orders.order_reference', $references);
            })
            ->select([
                'payments.*',
                'orders.order_reference',
                'orders.status as order_status',
                'orders.network_name',
                'orders.plan_name',
                'orders.data_size',
                'orders.beneficiary_number',
                DB::raw('NULL as registration_name'),
                DB::raw('NULL as customer_name'),
                DB::raw('NULL as phone_number'),
                DB::raw('NULL as location'),
                DB::raw("'data_order' as payment_scope"),
            ])
            ->get();

        $payments = $payments->merge($normalPayments);

        if (Schema::hasTable('afa_payments')) {
            $afaPayments = DB::table('afa_payments')
                ->leftJoin('afa_orders', 'afa_payments.order_id', '=', 'afa_orders.order_id')
                ->where(function ($query) use ($references) {
                    $query->whereIn('afa_payments.payment_reference', $references)
                        ->orWhereIn('afa_payments.provider_reference', $references)
                        ->orWhereIn('afa_orders.order_reference', $references);
                })
                ->select([
                    'afa_payments.*',
                    'afa_orders.order_reference',
                    'afa_orders.status as order_status',
                    DB::raw('NULL as network_name'),
                    DB::raw('NULL as plan_name'),
                    DB::raw('NULL as data_size'),
                    DB::raw('NULL as beneficiary_number'),
                    'afa_orders.registration_name',
                    'afa_orders.customer_name',
                    'afa_orders.phone_number',
                    'afa_orders.location',
                    DB::raw("'afa_order' as payment_scope"),
                ])
                ->get();

            $payments = $payments->merge($afaPayments);
        }

        if (Schema::hasTable('wallet_topups')) {
            $topups = DB::table('wallet_topups')
                ->where(function ($query) use ($references) {
                    $query->whereIn('wallet_topups.payment_reference', $references)
                        ->orWhereIn('wallet_topups.provider_reference', $references)
                        ->orWhereIn('wallet_topups.topup_reference', $references);
                })
                ->select([
                    'wallet_topups.topup_id as payment_id',
                    DB::raw('NULL as order_id'),
                    'wallet_topups.user_id',
                    'wallet_topups.topup_reference as order_reference',
                    DB::raw('NULL as order_status'),
                    'wallet_topups.payment_reference',
                    'wallet_topups.provider_reference',
                    'wallet_topups.provider_transaction_id',
                    'wallet_topups.payment_method',
                    'wallet_topups.amount',
                    'wallet_topups.total_amount',
                    'wallet_topups.charges',
                    'wallet_topups.currency',
                    'wallet_topups.status',
                    'wallet_topups.provider',
                    'wallet_topups.gateway_response',
                    'wallet_topups.channel',
                    'wallet_topups.customer_email',
                    'wallet_topups.paid_at',
                    'wallet_topups.verified_at',
                    'wallet_topups.created_at',
                    DB::raw('NULL as network_name'),
                    DB::raw('NULL as plan_name'),
                    DB::raw('NULL as data_size'),
                    DB::raw('NULL as beneficiary_number'),
                    DB::raw('NULL as registration_name'),
                    DB::raw('NULL as customer_name'),
                    DB::raw('NULL as phone_number'),
                    DB::raw('NULL as location'),
                    DB::raw("'wallet_topup' as payment_scope"),
                ])
                ->get();

            $payments = $payments->merge($topups);
        }

        return $payments->sortByDesc('created_at')->values();
    }

    private function findPaymentByReference(string $reference): ?object
    {
        return $this->findPaymentsByReferences(collect([$reference]))->first();
    }

    private function verifyFromPaystack(string $reference): array
    {
        $secretKey = config('paystack.secret_key') ?: env('PAYSTACK_SECRET_KEY');
        $baseUrl = rtrim(config('paystack.payment_url') ?: env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/');

        if (!$secretKey) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::timeout(20)
            ->retry(2, 700)
            ->withToken($secretKey)
            ->acceptJson()
            ->get($baseUrl . '/transaction/verify/' . urlencode($reference));

        if (!$response->successful()) {
            throw new \RuntimeException('Unable to verify payment from Paystack.');
        }

        $payload = $response->json();

        if (($payload['status'] ?? false) !== true) {
            throw new \RuntimeException($payload['message'] ?? 'Paystack verification failed.');
        }

        return $payload;
    }

    private function completeNormalPayment(object $oldPayment, string $reference, array $payload, array $data): void
    {
        $payment = DB::table('payments')->where('payment_id', $oldPayment->payment_id)->lockForUpdate()->first();

        if (!$payment) {
            throw new \RuntimeException('Payment not found.');
        }

        if ($payment->status === 'successful') {
            return;
        }

        $this->validateAmount($payment, $data);

        DB::table('payments')
            ->where('payment_id', $payment->payment_id)
            ->update($this->onlyExistingColumns('payments', $this->paymentUpdateData($payment, $reference, $payload, $data)));

        DB::table('orders')
            ->where('order_id', $payment->order_id)
            ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
            ->update([
                'status' => 'processing',
                'processing_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function completeAfaPayment(object $oldPayment, string $reference, array $payload, array $data): void
    {
        $payment = DB::table('afa_payments')->where('payment_id', $oldPayment->payment_id)->lockForUpdate()->first();

        if (!$payment) {
            throw new \RuntimeException('AFA payment not found.');
        }

        if ($payment->status === 'successful') {
            return;
        }

        $this->validateAmount($payment, $data);

        DB::table('afa_payments')
            ->where('payment_id', $payment->payment_id)
            ->update($this->onlyExistingColumns('afa_payments', $this->paymentUpdateData($payment, $reference, $payload, $data)));

        DB::table('afa_orders')
            ->where('order_id', $payment->order_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->update([
                'status' => 'processing',
                'processing_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function completeWalletTopup(object $oldTopup, string $reference, array $payload, array $data): void
    {
        $topup = DB::table('wallet_topups')->where('topup_id', $oldTopup->payment_id)->lockForUpdate()->first();

        if (!$topup) {
            throw new \RuntimeException('Wallet top-up not found.');
        }

        if ($topup->status !== 'successful') {
            $this->validateAmount($topup, $data, true);

            DB::table('wallet_topups')
                ->where('topup_id', $topup->topup_id)
                ->update($this->onlyExistingColumns('wallet_topups', $this->walletTopupUpdateData($topup, $reference, $payload, $data)));
        }

        $this->creditWalletIfNotCredited($topup);
    }

    private function manualNormalPayment(array $validated): void
    {
        $payment = DB::table('payments')->where('payment_id', $validated['payment_id'])->lockForUpdate()->first();

        if (!$payment) {
            throw new \RuntimeException('Payment not found.');
        }

        if ($payment->status === 'successful') {
            return;
        }

        DB::table('payments')
            ->where('payment_id', $payment->payment_id)
            ->update($this->onlyExistingColumns('payments', [
                'status' => 'successful',
                'verified_at' => now(),
                'manual_verified_at' => now(),
                'manual_verification_note' => $validated['note'] ?? null,
                'paid_at' => $payment->paid_at ?? now(),
                'updated_at' => now(),
            ]));

        DB::table('orders')
            ->where('order_id', $payment->order_id)
            ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
            ->update([
                'status' => 'processing',
                'processing_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function manualAfaPayment(array $validated): void
    {
        $payment = DB::table('afa_payments')->where('payment_id', $validated['payment_id'])->lockForUpdate()->first();

        if (!$payment) {
            throw new \RuntimeException('AFA payment not found.');
        }

        if ($payment->status === 'successful') {
            return;
        }

        DB::table('afa_payments')
            ->where('payment_id', $payment->payment_id)
            ->update($this->onlyExistingColumns('afa_payments', [
                'status' => 'successful',
                'verified_at' => now(),
                'manual_verified_at' => now(),
                'manual_verification_note' => $validated['note'] ?? null,
                'paid_at' => $payment->paid_at ?? now(),
                'updated_at' => now(),
            ]));

        DB::table('afa_orders')
            ->where('order_id', $payment->order_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->update([
                'status' => 'processing',
                'processing_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function manualWalletTopup(array $validated): void
    {
        $topup = DB::table('wallet_topups')->where('topup_id', $validated['payment_id'])->lockForUpdate()->first();

        if (!$topup) {
            throw new \RuntimeException('Wallet top-up not found.');
        }

        if ($topup->status !== 'successful') {
            DB::table('wallet_topups')
                ->where('topup_id', $topup->topup_id)
                ->update($this->onlyExistingColumns('wallet_topups', [
                    'status' => 'successful',
                    'verified_at' => now(),
                    'manual_verified_at' => now(),
                    'manual_verification_note' => $validated['note'] ?? null,
                    'paid_at' => $topup->paid_at ?? now(),
                    'updated_at' => now(),
                ]));
        }

        $this->creditWalletIfNotCredited($topup);
    }

    private function creditWalletIfNotCredited(object $topup): void
    {
        $alreadyCredited = DB::table('wallet_transactions')
            ->where('source', 'wallet_topup')
            ->where('source_id', $topup->topup_id)
            ->where('status', 'successful')
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        $user = DB::table('users')->where('user_id', $topup->user_id)->lockForUpdate()->first();

        if (!$user) {
            throw new \RuntimeException('Wallet user not found.');
        }

        $balanceBefore = round((float) ($user->wallet_balance ?? 0), 2);
        $amount = round((float) ($topup->amount ?? 0), 2);
        $balanceAfter = round($balanceBefore + $amount, 2);

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'wallet_balance' => $balanceAfter,
                'updated_at' => now(),
            ]);

        DB::table('wallet_transactions')->insert([
            'wallet_transaction_id' => (string) Str::uuid(),
            'user_id' => $user->user_id,
            'transaction_reference' => $this->generateWalletTransactionReference(),
            'transaction_type' => 'credit',
            'source' => 'wallet_topup',
            'source_id' => $topup->topup_id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => 'Wallet top-up verified by admin',
            'status' => 'successful',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validateAmount(object $payment, array $data, bool $walletTopup = false): void
    {
        if (empty($data)) {
            return;
        }

        $paidAmount = round(((float) ($data['amount'] ?? 0)) / 100, 2);

        $localAmount = $walletTopup
            ? round((float) ($payment->total_amount ?? $payment->amount ?? 0), 2)
            : round((float) ($payment->amount ?? 0), 2);

        if ($paidAmount < $localAmount) {
            throw new \RuntimeException('Paystack amount is less than local payment amount.');
        }
    }

    private function paymentUpdateData(object $payment, string $reference, array $payload, array $data): array
    {
        $authorization = $data['authorization'] ?? [];

        return [
            'status' => 'successful',
            'provider' => 'paystack',
            'provider_reference' => $reference,
            'provider_transaction_id' => $data['id'] ?? $payment->provider_transaction_id ?? null,
            'gateway_response' => $data['gateway_response'] ?? $payment->gateway_response ?? null,
            'channel' => $data['channel'] ?? $payment->channel ?? null,
            'fees' => isset($data['fees']) ? round(((float) $data['fees']) / 100, 2) : ($payment->fees ?? null),
            'authorization_code' => $authorization['authorization_code'] ?? $payment->authorization_code ?? null,
            'verification_response' => !empty($payload) ? json_encode($payload) : ($payment->verification_response ?? null),
            'paid_at' => $this->parsePaystackDate($data['paid_at'] ?? null) ?? now(),
            'verified_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function walletTopupUpdateData(object $topup, string $reference, array $payload, array $data): array
    {
        return [
            'status' => 'successful',
            'provider' => 'paystack',
            'provider_reference' => $reference,
            'provider_transaction_id' => $data['id'] ?? $topup->provider_transaction_id ?? null,
            'gateway_response' => $data['gateway_response'] ?? $topup->gateway_response ?? null,
            'channel' => $data['channel'] ?? $topup->channel ?? null,
            'fees' => isset($data['fees']) ? round(((float) $data['fees']) / 100, 2) : ($topup->fees ?? null),
            'api_response' => !empty($payload) ? json_encode($payload) : ($topup->api_response ?? null),
            'paid_at' => $this->parsePaystackDate($data['paid_at'] ?? null) ?? now(),
            'verified_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function parsePaystackDate(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function generateWalletTransactionReference(): string
    {
        do {
            $reference = 'WT' . strtoupper(Str::random(10));
        } while (DB::table('wallet_transactions')->where('transaction_reference', $reference)->exists());

        return $reference;
    }
}