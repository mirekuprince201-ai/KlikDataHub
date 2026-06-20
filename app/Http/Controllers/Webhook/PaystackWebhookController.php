<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $secretKey = config('paystack.secret_key') ?: env('PAYSTACK_SECRET_KEY');

        if (!$secretKey) {
            Log::critical('[PAYSTACK WEBHOOK] Missing Paystack secret key.');
            return response()->json(['status' => false, 'message' => 'Server configuration error.'], 500);
        }

        if (!$signature) {
            Log::warning('[PAYSTACK WEBHOOK] Missing signature.');
            return response()->json(['status' => false, 'message' => 'Missing signature.'], 401);
        }

        $computedSignature = hash_hmac('sha512', $payload, $secretKey);

        if (!hash_equals($computedSignature, $signature)) {
            Log::warning('[PAYSTACK WEBHOOK] Invalid signature.');
            return response()->json(['status' => false, 'message' => 'Invalid signature.'], 401);
        }

        $event = $request->input('event');
        $reference = $request->input('data.reference');
        $transactionId = $request->input('data.id');
        $amount = $request->input('data.amount');
        $channel = $request->input('data.channel');
        $customerEmail = $request->input('data.customer.email');

        $this->logWebhook($event, $reference, $transactionId, $customerEmail, $amount, $channel, $payload, $signature);

        if ($event !== 'charge.success') {
            $this->markWebhookLog($reference, 'ignored');
            return response()->json(['status' => true, 'message' => 'Webhook acknowledged.'], 200);
        }

        if (!$reference) {
            $this->markWebhookLog($reference, 'missing_reference');
            return response()->json(['status' => true, 'message' => 'Reference missing.'], 200);
        }

        try {
            $paystackPayload = $this->verifyFromPaystack($reference);
            $data = $paystackPayload['data'] ?? [];

            if (strtolower((string) ($data['status'] ?? '')) !== 'success') {
                $this->markWebhookLog($reference, 'verification_failed');
                return response()->json(['status' => true, 'message' => 'Transaction not successful.'], 200);
            }

            if ($this->processWalletTopup($reference, $paystackPayload, $data)) {
                return response()->json(['status' => true, 'message' => 'Wallet top-up processed.'], 200);
            }

            if ($this->processResultCheckerPayment($reference, $paystackPayload, $data, $payload, $signature)) {
                return response()->json(['status' => true, 'message' => 'Result checker payment processed.'], 200);
            }

            if ($this->processAfaPayment($reference, $paystackPayload, $data)) {
                return response()->json(['status' => true, 'message' => 'AFA payment processed.'], 200);
            }

            if ($this->processNormalPayment($reference, $paystackPayload, $data)) {
                return response()->json(['status' => true, 'message' => 'Order payment processed.'], 200);
            }

            $this->markWebhookLog($reference, 'payment_not_found');

            Log::warning('[PAYSTACK WEBHOOK] Reference not found locally.', [
                'reference' => $reference,
            ]);

            return response()->json(['status' => true, 'message' => 'Payment not found.'], 200);
        } catch (\Throwable $e) {
            Log::error('[PAYSTACK WEBHOOK] Processing failed.', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $this->markWebhookLog($reference, 'failed', $e->getMessage());

            return response()->json(['status' => true, 'message' => 'Webhook received.'], 200);
        }
    }

    private function processResultCheckerPayment(string $reference, array $paystackPayload, array $data, string $rawPayload, string $signature): bool
    {
        if (!Schema::hasTable('result_checker_payments')) {
            return false;
        }

        $found = DB::table('result_checker_payments')
            ->where('payment_reference', $reference)
            ->first();

        if (!$found) {
            return false;
        }

        DB::transaction(function () use ($found, $reference, $paystackPayload, $data, $rawPayload, $signature) {
            $payment = DB::table('result_checker_payments')
                ->where('payment_id', $found->payment_id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw new \RuntimeException('Result checker payment no longer exists.');
            }

            $this->updateResultCheckerWebhookContent($payment, $reference, $data, $rawPayload, $signature);

            if ($payment->payment_status === 'success') {
                $this->markWebhookLog($reference, 'already_processed');
                return;
            }

            $this->validateResultCheckerAmount($payment, $data);

            $authorization = $data['authorization'] ?? [];
            $customer = $data['customer'] ?? [];

            DB::table('result_checker_payments')
                ->where('payment_id', $payment->payment_id)
                ->update([
                    'payment_status' => 'success',
                    'paystack_transaction_id' => $data['id'] ?? null,
                    'gateway_response' => $data['gateway_response'] ?? null,
                    'channel' => $data['channel'] ?? null,
                    'fees' => isset($data['fees']) ? round(((float) $data['fees']) / 100, 2) : 0,
                    'customer_email' => $customer['email'] ?? $payment->customer_email,
                    'customer_code' => $customer['customer_code'] ?? null,
                    'authorization_code' => $authorization['authorization_code'] ?? null,
                    'card_type' => $authorization['card_type'] ?? null,
                    'bank' => $authorization['bank'] ?? null,
                    'last4' => $authorization['last4'] ?? null,
                    'exp_month' => $authorization['exp_month'] ?? null,
                    'exp_year' => $authorization['exp_year'] ?? null,
                    'paid_at' => $this->parsePaystackDate($data['paid_at'] ?? null) ?? now(),
                    'verified_at' => now(),
                    'verify_response' => json_encode($paystackPayload),
                    'webhook_processed_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->deliverResultCheckerOrder($payment->order_id);

            $this->markWebhookLog($reference, 'processed');
        });

        return true;
    }

    private function updateResultCheckerWebhookContent(object $payment, string $reference, array $data, string $rawPayload, string $signature): void
    {
        DB::table('result_checker_payments')
            ->where('payment_id', $payment->payment_id)
            ->update([
                'webhook_event' => 'charge.success',
                'webhook_status' => $data['status'] ?? null,
                'webhook_signature' => $signature,
                'webhook_content' => $rawPayload,
                'webhook_received_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function validateResultCheckerAmount(object $payment, array $data): void
    {
        $paidSubunit = (int) ($data['amount'] ?? 0);
        $localSubunit = (int) ($payment->amount_subunit ?? 0);

        $paidCurrency = strtoupper((string) ($data['currency'] ?? 'GHS'));
        $localCurrency = strtoupper((string) ($payment->currency ?? 'GHS'));

        if ($paidSubunit < $localSubunit) {
            throw new \RuntimeException('Paystack amount is less than result checker payment amount.');
        }

        if ($paidCurrency !== $localCurrency) {
            throw new \RuntimeException('Paystack currency does not match result checker payment currency.');
        }
    }

    private function deliverResultCheckerOrder(string $orderId): void
    {
        if (
            !Schema::hasTable('result_checker_orders') ||
            !Schema::hasTable('result_checker_order_items') ||
            !Schema::hasTable('result_checker_vouchers')
        ) {
            throw new \RuntimeException('Result checker order tables are missing.');
        }

        $order = DB::table('result_checker_orders')
            ->where('order_id', $orderId)
            ->lockForUpdate()
            ->first();

        if (!$order) {
            throw new \RuntimeException('Result checker order not found.');
        }

        if ($order->order_status === 'completed') {
            return;
        }

        $existingItems = DB::table('result_checker_order_items')
            ->where('order_id', $order->order_id)
            ->count();

        if ($existingItems > 0) {
            DB::table('result_checker_orders')
                ->where('order_id', $order->order_id)
                ->update([
                    'order_status' => 'completed',
                    'delivered_at' => $order->delivered_at ?? now(),
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('result_checker_orders')
            ->where('order_id', $order->order_id)
            ->update([
                'order_status' => 'processing',
                'updated_at' => now(),
            ]);

        $vouchers = DB::table('result_checker_vouchers')
            ->where('checker_id', $order->checker_id)
            ->where('sold_status', 'available')
            ->lockForUpdate()
            ->limit((int) $order->quantity)
            ->get();

        if ($vouchers->count() < (int) $order->quantity) {
            throw new \RuntimeException('Not enough available result checker vouchers.');
        }

        foreach ($vouchers as $voucher) {
            DB::table('result_checker_order_items')->insert([
                'item_id' => (string) Str::uuid(),
                'order_id' => $order->order_id,
                'checker_id' => $order->checker_id,
                'voucher_id' => $voucher->voucher_id,
                'checker_name' => $order->checker_name,
                'checker_type' => $order->checker_type,
                'serial_number' => $voucher->serial_number,
                'voucher_pin' => $voucher->voucher_pin,
                'unit_price' => $order->unit_price,
                'item_status' => 'delivered',
                'delivered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('result_checker_vouchers')
                ->where('voucher_id', $voucher->voucher_id)
                ->update([
                    'sold_status' => 'sold',
                    'sold_to' => $order->customer_email ?: $order->customer_phone,
                    'sold_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        DB::table('result_checker_orders')
            ->where('order_id', $order->order_id)
            ->update([
                'order_status' => 'completed',
                'delivered_at' => now(),
                'updated_at' => now(),
            ]);

        $items = DB::table('result_checker_order_items')
            ->where('order_id', $order->order_id)
            ->get();

        $this->sendResultCheckerEmail($order, $items);
        $this->sendResultCheckerSms($order, $items);
    }

    private function sendResultCheckerEmail(object $order, $items): void
    {
        if (empty($order->customer_email)) {
            return;
        }

        try {
            Mail::raw($this->resultCheckerMessage($order, $items), function ($message) use ($order) {
                $message->to($order->customer_email)
                    ->subject('Your Result Checker PIN - ' . $order->order_number);
            });
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER] Email delivery failed.', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendResultCheckerSms(object $order, $items): void
    {
        if (empty($order->customer_phone)) {
            return;
        }

        try {
            if (filter_var(env('FAKE_SMS', false), FILTER_VALIDATE_BOOLEAN)) {
                Log::info('[RESULT CHECKER] Fake SMS enabled.', [
                    'phone' => $order->customer_phone,
                    'message' => $this->resultCheckerMessage($order, $items),
                ]);

                return;
            }

            Http::timeout(20)->get(env('SMSNOTIFY_API_BASE'), [
                'key' => env('SMSNOTIFY_API_KEY'),
                'to' => $order->customer_phone,
                'msg' => $this->resultCheckerSmsMessage($order, $items),
                'sender_id' => env('SMS_SENDER_ID', 'KLIKDATAHUB'),
            ]);
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER] SMS delivery failed.', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resultCheckerMessage(object $order, $items): string
    {
        $lines = [];

        $lines[] = 'Dear ' . ($order->customer_name ?: 'Customer') . ',';
        $lines[] = 'Your ' . $order->checker_name . ' order has been completed.';
        $lines[] = 'Order No: ' . $order->order_number;
        $lines[] = 'Amount: ' . $order->currency . ' ' . number_format((float) $order->total_amount, 2);
        $lines[] = '';

        foreach ($items as $index => $item) {
            $lines[] = 'Voucher ' . ($index + 1);
            $lines[] = 'Serial Number: ' . $item->serial_number;
            $lines[] = 'PIN: ' . $item->voucher_pin;
            $lines[] = '';
        }

        $lines[] = 'Thank you for using KlikDataHub.';

        return implode("\n", $lines);
    }

    private function resultCheckerSmsMessage(object $order, $items): string
    {
        $parts = [];

        foreach ($items as $index => $item) {
            $parts[] = 'V' . ($index + 1) . ': Serial ' . $item->serial_number . ', PIN ' . $item->voucher_pin;
        }

        return 'KlikDataHub: ' . $order->checker_name . ' Order ' . $order->order_number . '. ' . implode(' | ', $parts);
    }

    private function processNormalPayment(string $reference, array $paystackPayload, array $data): bool
    {
        if (!Schema::hasTable('payments')) return false;

        $found = DB::table('payments')
            ->where('payment_reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (!$found) return false;

        DB::transaction(function () use ($found, $reference, $paystackPayload, $data) {
            $payment = DB::table('payments')
                ->where('payment_id', $found->payment_id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw new \RuntimeException('Payment no longer exists.');
            }

            if ($payment->status === 'successful') {
                $this->safeUpdate('payments', 'payment_id', $payment->payment_id, [
                    'webhook_received_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->markWebhookLog($reference, 'already_processed');
                return;
            }

            $this->validateAmount($payment, $data);

            $this->safeUpdate('payments', 'payment_id', $payment->payment_id, $this->paymentUpdateData(
                $payment,
                $reference,
                $paystackPayload,
                $data,
                true
            ));

            if (!empty($payment->order_id) && Schema::hasTable('orders')) {
                DB::table('orders')
                    ->where('order_id', $payment->order_id)
                    ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
                    ->update([
                        'status' => 'processing',
                        'processing_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $this->markWebhookLog($reference, 'processed');
        });

        return true;
    }

    private function processAfaPayment(string $reference, array $paystackPayload, array $data): bool
    {
        if (!Schema::hasTable('afa_payments')) return false;

        $found = DB::table('afa_payments')
            ->where('payment_reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (!$found) return false;

        DB::transaction(function () use ($found, $reference, $paystackPayload, $data) {
            $payment = DB::table('afa_payments')
                ->where('payment_id', $found->payment_id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw new \RuntimeException('AFA payment no longer exists.');
            }

            if ($payment->status === 'successful') {
                $this->safeUpdate('afa_payments', 'payment_id', $payment->payment_id, [
                    'webhook_received_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->markWebhookLog($reference, 'already_processed');
                return;
            }

            $this->validateAmount($payment, $data);

            $this->safeUpdate('afa_payments', 'payment_id', $payment->payment_id, $this->paymentUpdateData(
                $payment,
                $reference,
                $paystackPayload,
                $data,
                true
            ));

            if (!empty($payment->order_id) && Schema::hasTable('afa_orders')) {
                DB::table('afa_orders')
                    ->where('order_id', $payment->order_id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->update([
                        'status' => 'processing',
                        'processing_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $this->markWebhookLog($reference, 'processed');
        });

        return true;
    }

    private function processWalletTopup(string $reference, array $paystackPayload, array $data): bool
    {
        if (!Schema::hasTable('wallet_topups')) return false;

        $found = DB::table('wallet_topups')
            ->where('payment_reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (!$found) return false;

        DB::transaction(function () use ($found, $reference, $paystackPayload, $data) {
            $topup = DB::table('wallet_topups')
                ->where('topup_id', $found->topup_id)
                ->lockForUpdate()
                ->first();

            if (!$topup) {
                throw new \RuntimeException('Wallet top-up no longer exists.');
            }

            if ($topup->status === 'successful') {
                $this->safeUpdate('wallet_topups', 'topup_id', $topup->topup_id, [
                    'webhook_received_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->markWebhookLog($reference, 'already_processed');
                return;
            }

            $this->validateAmount($topup, $data, true);

            $this->safeUpdate('wallet_topups', 'topup_id', $topup->topup_id, $this->walletTopupUpdateData(
                $topup,
                $reference,
                $paystackPayload,
                $data
            ));

            $alreadyCredited = DB::table('wallet_transactions')
                ->where('source', 'wallet_topup')
                ->where('source_id', $topup->topup_id)
                ->where('status', 'successful')
                ->exists();

            if ($alreadyCredited) {
                $this->markWebhookLog($reference, 'already_processed');
                return;
            }

            $user = DB::table('users')
                ->where('user_id', $topup->user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new \RuntimeException('Wallet top-up user not found.');
            }

            $balanceBefore = round((float) ($user->wallet_balance ?? 0), 2);
            $creditAmount = round((float) ($topup->amount ?? 0), 2);
            $balanceAfter = round($balanceBefore + $creditAmount, 2);

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
                'amount' => $creditAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Wallet top-up verified by Paystack webhook',
                'status' => 'successful',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->markWebhookLog($reference, 'processed');
        });

        return true;
    }

    private function verifyFromPaystack(string $reference): array
    {
        $secretKey = config('paystack.secret_key') ?: env('PAYSTACK_SECRET_KEY');
        $baseUrl = rtrim(config('paystack.payment_url') ?: env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/');

        $response = Http::timeout(20)
            ->retry(2, 700)
            ->withToken($secretKey)
            ->acceptJson()
            ->get($baseUrl . '/transaction/verify/' . urlencode($reference));

        if (!$response->successful()) {
            Log::warning('[PAYSTACK WEBHOOK] Verify HTTP failed.', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Unable to verify transaction from Paystack.');
        }

        $payload = $response->json();

        if (($payload['status'] ?? false) !== true) {
            throw new \RuntimeException($payload['message'] ?? 'Paystack verification failed.');
        }

        return $payload;
    }

    private function validateAmount(object $payment, array $data, bool $walletTopup = false): void
    {
        $paidAmount = round(((float) ($data['amount'] ?? 0)) / 100, 2);
        $currency = strtoupper((string) ($data['currency'] ?? 'GHS'));

        $localAmount = $walletTopup
            ? round((float) ($payment->total_amount ?? $payment->amount ?? 0), 2)
            : round((float) ($payment->amount ?? 0), 2);

        $localCurrency = strtoupper((string) ($payment->currency ?? 'GHS'));

        if ($paidAmount < $localAmount) {
            throw new \RuntimeException('Paystack amount is less than local payment amount.');
        }

        if ($currency !== $localCurrency) {
            throw new \RuntimeException('Paystack currency does not match local currency.');
        }
    }

    private function paymentUpdateData(object $payment, string $reference, array $payload, array $data, bool $successful): array
    {
        $authorization = $data['authorization'] ?? [];

        return [
            'status' => $successful ? 'successful' : 'failed',
            'provider' => 'paystack',
            'provider_reference' => $reference,
            'provider_transaction_id' => $data['id'] ?? $payment->provider_transaction_id ?? null,
            'gateway_response' => $data['gateway_response'] ?? $payment->gateway_response ?? null,
            'channel' => $data['channel'] ?? $payment->channel ?? null,
            'fees' => isset($data['fees']) ? round(((float) $data['fees']) / 100, 2) : ($payment->fees ?? null),
            'authorization_code' => $authorization['authorization_code'] ?? $payment->authorization_code ?? null,
            'verification_response' => json_encode($payload),
            'paid_at' => $this->parsePaystackDate($data['paid_at'] ?? null) ?? now(),
            'verified_at' => now(),
            'webhook_received_at' => now(),
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
            'api_response' => json_encode($payload),
            'paid_at' => $this->parsePaystackDate($data['paid_at'] ?? null) ?? now(),
            'verified_at' => now(),
            'webhook_received_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function safeUpdate(string $table, string $key, string $value, array $data): void
    {
        $filtered = [];

        foreach ($data as $column => $columnValue) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $columnValue;
            }
        }

        if (!empty($filtered)) {
            DB::table($table)->where($key, $value)->update($filtered);
        }
    }

    private function parsePaystackDate(?string $date): ?Carbon
    {
        if (!$date) return null;

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function logWebhook($event, $reference, $transactionId, $customerEmail, $amount, $channel, $payload, $signature): void
    {
        try {
            if (!Schema::hasTable('paystack_webhook_logs')) return;

            DB::table('paystack_webhook_logs')->insert([
                'webhook_log_id' => (string) Str::uuid(),
                'event' => $event,
                'reference' => $reference,
                'paystack_transaction_id' => $transactionId,
                'customer_email' => $customerEmail,
                'amount' => $amount ? round(((float) $amount) / 100, 2) : null,
                'channel' => $channel,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'received',
                'received_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[PAYSTACK WEBHOOK] Log save failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function markWebhookLog(?string $reference, string $status, ?string $error = null): void
    {
        try {
            if (!$reference || !Schema::hasTable('paystack_webhook_logs')) return;

            $data = [
                'status' => $status,
                'updated_at' => now(),
            ];

            if (in_array($status, ['processed', 'already_processed'], true)) {
                $data['processed_at'] = now();
            }

            if ($error) {
                $data['error_message'] = Str::limit($error, 1000);
            }

            DB::table('paystack_webhook_logs')
                ->where('reference', $reference)
                ->latest('created_at')
                ->limit(1)
                ->update($data);
        } catch (\Throwable $e) {
            Log::error('[PAYSTACK WEBHOOK] Log update failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateWalletTransactionReference(): string
    {
        do {
            $reference = 'WT' . strtoupper(Str::random(10));
        } while (
            DB::table('wallet_transactions')
                ->where('transaction_reference', $reference)
                ->exists()
        );

        return $reference;
    }
}