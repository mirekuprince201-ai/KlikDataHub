<?php

namespace App\Http\Controllers\User\ResultChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResultCheckerPaymentController extends Controller
{
    private float $paystackChargeRate = 0.025;

    public function initialize(Request $request)
    {
        $user = Auth::guard('user')->user();

        $validated = $request->validate([
            'checker_id' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_name' => [$user ? 'nullable' : 'required', 'nullable', 'string', 'max:150'],
            'customer_email' => [$user ? 'nullable' : 'required', 'nullable', 'email', 'max:150'],
            'customer_phone' => [$user ? 'nullable' : 'required', 'nullable', 'string', 'max:30'],
        ]);

        try {
            $result = DB::transaction(function () use ($request, $validated, $user) {
                $customer = $this->resolveCustomer($validated, $user);

                $checker = DB::table('result_checkers')
                    ->where('checker_id', $validated['checker_id'])
                    ->whereRaw('LOWER(status) = ?', ['active'])
                    ->lockForUpdate()
                    ->first();

                if (!$checker) {
                    throw new \RuntimeException('Result checker not found or inactive.');
                }

                $quantity = (int) $validated['quantity'];

                $availableStock = DB::table('result_checker_vouchers')
                    ->where('checker_id', $checker->checker_id)
                    ->where('sold_status', 'available')
                    ->lockForUpdate()
                    ->count();

                if ($availableStock < $quantity) {
                    throw new \RuntimeException('Only ' . $availableStock . ' voucher(s) available.');
                }

                $unitPrice = $this->resolveUnitPrice($checker->checker_id, $quantity, (float) $checker->selling_price);
                $subtotal = round(((float) $checker->selling_price) * $quantity, 2);
                $checkerTotal = round($unitPrice * $quantity, 2);
                $paystackCharge = $this->paystackCharge($checkerTotal);
                $totalPayable = round($checkerTotal + $paystackCharge, 2);
                $discount = round(max(0, $subtotal - $checkerTotal), 2);

                if ($totalPayable <= 0) {
                    throw new \RuntimeException('Invalid order amount.');
                }

                $orderId = (string) Str::uuid();
                $paymentId = (string) Str::uuid();
                $orderNumber = $this->generateOrderNumber();
                $reference = $this->generatePaymentReference();

                DB::table('result_checker_orders')->insert([
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'user_id' => $user?->user_id,
                    'checker_id' => $checker->checker_id,
                    'customer_name' => $customer['name'],
                    'customer_email' => $customer['email'],
                    'customer_phone' => $customer['phone'],
                    'checker_name' => $checker->checker_name,
                    'checker_type' => $checker->checker_type,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $totalPayable,
                    'currency' => $this->currency(),
                    'order_status' => 'pending',
                    'notes' => 'Checker total: ' . $checkerTotal . ', Paystack charge: ' . $paystackCharge,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('result_checker_payments')->insert([
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'provider' => 'paystack',
                    'payment_reference' => $reference,
                    'amount' => $totalPayable,
                    'amount_subunit' => $this->toSubunit($totalPayable),
                    'currency' => $this->currency(),
                    'payment_status' => 'pending',
                    'customer_email' => $customer['email'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $paystack = $this->initializePaystack($customer['email'], $totalPayable, $reference, $orderId, $orderNumber);

                $accessCode = $paystack['data']['access_code'] ?? null;

                if (!$accessCode) {
                    throw new \RuntimeException('Paystack access code was not returned.');
                }

                DB::table('result_checker_payments')
                    ->where('payment_id', $paymentId)
                    ->update([
                        'payment_status' => 'initialized',
                        'access_code' => $accessCode,
                        'authorization_url' => $paystack['data']['authorization_url'] ?? null,
                        'initialize_response' => json_encode($paystack),
                        'initialized_at' => now(),
                        'updated_at' => now(),
                    ]);

                return [
                    'status' => true,
                    'message' => 'Payment initialized successfully.',
                    'access_code' => $accessCode,
                    'reference' => $reference,
                    'order_number' => $orderNumber,
                    'checker_total' => $checkerTotal,
                    'paystack_charge' => $paystackCharge,
                    'amount' => $totalPayable,
                    'currency' => $this->currency(),
                ];
            });

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER PAYMENT INIT] Failed.', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:80'],
        ]);

        try {
            $reference = $validated['reference'];
            $verify = $this->verifyPaystack($reference);

            $orderNumber = DB::transaction(function () use ($reference, $verify) {
                $payment = DB::table('result_checker_payments')
                    ->where('payment_reference', $reference)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    throw new \RuntimeException('Payment record not found.');
                }

                $order = DB::table('result_checker_orders')
                    ->where('order_id', $payment->order_id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \RuntimeException('Order record not found.');
                }

                if ($payment->payment_status === 'success' && $order->order_status === 'completed') {
                    return $order->order_number;
                }

                $data = $verify['data'] ?? [];
                $status = strtolower((string) ($data['status'] ?? 'failed'));
                $verifiedAmount = (int) ($data['amount'] ?? 0);
                $verifiedCurrency = strtoupper((string) ($data['currency'] ?? $this->currency()));

                if ($status !== 'success') {
                    DB::table('result_checker_payments')
                        ->where('payment_id', $payment->payment_id)
                        ->update([
                            'payment_status' => 'failed',
                            'failed_at' => now(),
                            'verified_at' => now(),
                            'verify_response' => json_encode($verify),
                            'updated_at' => now(),
                        ]);

                    throw new \RuntimeException('Payment was not successful.');
                }

                if ($verifiedAmount !== (int) $payment->amount_subunit) {
                    throw new \RuntimeException('Payment amount mismatch detected.');
                }

                if ($verifiedCurrency !== strtoupper((string) $payment->currency)) {
                    throw new \RuntimeException('Payment currency mismatch detected.');
                }

                $this->updateSuccessfulPaystackPayment($payment, $verify);
                $this->deliverOrder($payment->order_id);

                return $order->order_number;
            });

            return response()->json([
                'status' => true,
                'message' => 'Payment verified. Voucher PIN delivered.',
                'redirect_url' => route('user.result-checkers.payment.success', $orderNumber),
            ]);
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER PAYMENT VERIFY] Failed.', [
                'reference' => $validated['reference'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function payWithWallet(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Please login to pay with wallet.',
            ], 401);
        }

        $validated = $request->validate([
            'checker_id' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $orderNumber = DB::transaction(function () use ($request, $validated, $user) {
                $customer = $this->resolveCustomer($validated, $user);

                $checker = DB::table('result_checkers')
                    ->where('checker_id', $validated['checker_id'])
                    ->whereRaw('LOWER(status) = ?', ['active'])
                    ->lockForUpdate()
                    ->first();

                if (!$checker) {
                    throw new \RuntimeException('Result checker not found or inactive.');
                }

                $quantity = (int) $validated['quantity'];

                $availableStock = DB::table('result_checker_vouchers')
                    ->where('checker_id', $checker->checker_id)
                    ->where('sold_status', 'available')
                    ->lockForUpdate()
                    ->count();

                if ($availableStock < $quantity) {
                    throw new \RuntimeException('Only ' . $availableStock . ' voucher(s) available.');
                }

                $unitPrice = $this->resolveUnitPrice($checker->checker_id, $quantity, (float) $checker->selling_price);
                $subtotal = round(((float) $checker->selling_price) * $quantity, 2);
                $total = round($unitPrice * $quantity, 2);
                $discount = round(max(0, $subtotal - $total), 2);

                $freshUser = DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->whereRaw('LOWER(account_status) = ?', ['active'])
                    ->lockForUpdate()
                    ->first();

                if (!$freshUser) {
                    throw new \RuntimeException('Your account is not active.');
                }

                if ((float) $freshUser->wallet_balance < $total) {
                    throw new \RuntimeException('Insufficient wallet balance.');
                }

                $orderId = (string) Str::uuid();
                $paymentId = (string) Str::uuid();
                $orderNumber = $this->generateOrderNumber();
                $reference = $this->generatePaymentReference('W');

                DB::table('result_checker_orders')->insert([
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'user_id' => $freshUser->user_id,
                    'checker_id' => $checker->checker_id,
                    'customer_name' => $customer['name'],
                    'customer_email' => $customer['email'],
                    'customer_phone' => $customer['phone'],
                    'checker_name' => $checker->checker_name,
                    'checker_type' => $checker->checker_type,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'currency' => $this->currency(),
                    'order_status' => 'pending',
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')
                    ->where('user_id', $freshUser->user_id)
                    ->where('wallet_balance', '>=', $total)
                    ->update([
                        'wallet_balance' => DB::raw('wallet_balance - ' . $total),
                        'updated_at' => now(),
                    ]);

                DB::table('result_checker_payments')->insert([
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'provider' => 'wallet',
                    'payment_reference' => $reference,
                    'amount' => $total,
                    'amount_subunit' => $this->toSubunit($total),
                    'currency' => $this->currency(),
                    'payment_status' => 'success',
                    'customer_email' => $customer['email'],
                    'gateway_response' => 'Wallet payment successful',
                    'channel' => 'wallet',
                    'paid_at' => now(),
                    'verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->deliverOrder($orderId);

                return $orderNumber;
            });

            return response()->json([
                'status' => true,
                'message' => 'Wallet payment successful. Voucher PIN delivered.',
                'redirect_url' => route('user.result-checkers.payment.success', $orderNumber),
            ]);
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER WALLET PAYMENT] Failed.', [
                'user_id' => $user->user_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function success(string $orderNumber)
    {
        $user = Auth::guard('user')->user();

        $orderQuery = DB::table('result_checker_orders')
            ->where('order_number', $orderNumber);

        if ($user) {
            $orderQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->user_id)
                    ->orWhere('customer_email', $user->email)
                    ->orWhere('customer_phone', $user->phone);
            });
        }

        $order = $orderQuery->first();

        if (!$order) {
            abort(404);
        }

        $items = DB::table('result_checker_order_items')
            ->where('order_id', $order->order_id)
            ->orderBy('created_at')
            ->get();

        return view('users.result-checkers.success', compact('order', 'items'));
    }

    private function deliverOrder(string $orderId): void
    {
        $order = DB::table('result_checker_orders')
            ->where('order_id', $orderId)
            ->lockForUpdate()
            ->first();

        if (!$order) {
            throw new \RuntimeException('Order not found.');
        }

        if ($order->order_status === 'completed') {
            return;
        }

        $existingItems = DB::table('result_checker_order_items')
            ->where('order_id', $orderId)
            ->count();

        if ($existingItems > 0) {
            DB::table('result_checker_orders')
                ->where('order_id', $orderId)
                ->update([
                    'order_status' => 'completed',
                    'delivered_at' => $order->delivered_at ?? now(),
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('result_checker_orders')
            ->where('order_id', $orderId)
            ->update([
                'order_status' => 'processing',
                'updated_at' => now(),
            ]);

        $vouchers = DB::table('result_checker_vouchers')
            ->where('checker_id', $order->checker_id)
            ->where('sold_status', 'available')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->limit((int) $order->quantity)
            ->get();

        if ($vouchers->count() < (int) $order->quantity) {
            throw new \RuntimeException('Not enough vouchers available for delivery.');
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
                ->where('sold_status', 'available')
                ->update([
                    'sold_status' => 'sold',
                    'sold_to' => $order->customer_email ?: $order->customer_phone,
                    'sold_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        DB::table('result_checker_orders')
            ->where('order_id', $orderId)
            ->update([
                'order_status' => 'completed',
                'delivered_at' => now(),
                'updated_at' => now(),
            ]);

        $items = DB::table('result_checker_order_items')
            ->where('order_id', $orderId)
            ->orderBy('created_at')
            ->get();

        $this->sendDeliveryEmail($order, $items);
        $this->sendDeliverySms($order, $items);
    }

    private function updateSuccessfulPaystackPayment(object $payment, array $verify): void
    {
        $data = $verify['data'] ?? [];
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
                'paid_at' => now(),
                'verified_at' => now(),
                'verify_response' => json_encode($verify),
                'updated_at' => now(),
            ]);
    }

    private function resolveCustomer(array $validated, $user): array
    {
        if ($user) {
            return [
                'name' => $user->full_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'email' => strtolower(trim((string) $user->email)),
                'phone' => preg_replace('/\s+/', '', (string) $user->phone),
            ];
        }

        return [
            'name' => trim((string) ($validated['customer_name'] ?? 'Guest Customer')),
            'email' => strtolower(trim((string) ($validated['customer_email'] ?? ''))),
            'phone' => preg_replace('/\s+/', '', (string) ($validated['customer_phone'] ?? '')),
        ];
    }

    private function resolveUnitPrice(string $checkerId, int $quantity, float $defaultPrice): float
    {
        $discount = DB::table('result_checker_bulk_discounts')
            ->where('checker_id', $checkerId)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('unit_price')
            ->first();

        $price = $discount ? (float) $discount->unit_price : (float) $defaultPrice;

        if ($price <= 0) {
            throw new \RuntimeException('Invalid checker price.');
        }

        return round($price, 2);
    }

    private function initializePaystack(string $email, float $amount, string $reference, string $orderId, string $orderNumber): array
    {
        $secret = env('PAYSTACK_SECRET_KEY');

        if (!$secret) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::timeout(25)
            ->retry(2, 700)
            ->withToken($secret)
            ->acceptJson()
            ->post(rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/') . '/transaction/initialize', [
                'email' => $email,
                'amount' => $this->toSubunit($amount),
                'currency' => $this->currency(),
                'reference' => $reference,
                'callback_url' => route('user.result-checkers.payment.success', $orderNumber),
                'metadata' => [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'type' => 'result_checker_order',
                    'charge_rate' => $this->paystackChargeRate,
                ],
            ]);

        if (!$response->successful() || $response->json('status') !== true) {
            throw new \RuntimeException($response->json('message') ?: 'Unable to initialize Paystack payment.');
        }

        return $response->json();
    }

    private function verifyPaystack(string $reference): array
    {
        $secret = env('PAYSTACK_SECRET_KEY');

        if (!$secret) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::timeout(25)
            ->retry(2, 700)
            ->withToken($secret)
            ->acceptJson()
            ->get(rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/') . '/transaction/verify/' . rawurlencode($reference));

        if (!$response->successful() || $response->json('status') !== true) {
            throw new \RuntimeException($response->json('message') ?: 'Unable to verify Paystack payment.');
        }

        return $response->json();
    }

    private function sendDeliveryEmail(object $order, $items): void
    {
        if (empty($order->customer_email)) {
            return;
        }

        try {
            Mail::html($this->deliveryEmailHtml($order, $items), function ($message) use ($order) {
                $message->to($order->customer_email)
                    ->subject('Your Result Checker PIN - ' . $order->order_number);
            });
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER] Email failed.', [
                'order_id' => $order->order_id,
                'email' => $order->customer_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendDeliverySms(object $order, $items): void
    {
        if (empty($order->customer_phone)) {
            return;
        }

        try {
            if (filter_var(env('FAKE_SMS', false), FILTER_VALIDATE_BOOLEAN)) {
                Log::info('[RESULT CHECKER] Fake SMS enabled.', [
                    'phone' => $order->customer_phone,
                    'message' => $this->deliverySmsMessage($order, $items),
                ]);
                return;
            }

            Http::timeout(20)->get(env('SMSNOTIFY_API_BASE'), [
                'key' => env('SMSNOTIFY_API_KEY'),
                'to' => $order->customer_phone,
                'msg' => $this->deliverySmsMessage($order, $items),
                'sender_id' => env('SMS_SENDER_ID', 'KLIKDATAHUB'),
            ]);
        } catch (\Throwable $e) {
            Log::error('[RESULT CHECKER] SMS failed.', [
                'order_id' => $order->order_id,
                'phone' => $order->customer_phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deliveryEmailHtml(object $order, $items): string
    {
        $voucherHtml = '';

        foreach ($items as $index => $item) {
            $voucherHtml .= '
                <tr>
                    <td style="padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#f8fafc;">
                        <p style="margin:0 0 12px;color:#2563eb;font-weight:800;font-size:13px;">Voucher ' . ($index + 1) . '</p>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:8px 0;color:#64748b;font-size:13px;">Serial Number</td>
                                <td align="right" style="padding:8px 0;color:#020617;font-size:16px;font-weight:800;">' . e($item->serial_number) . '</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;color:#64748b;font-size:13px;">PIN</td>
                                <td align="right" style="padding:8px 0;color:#020617;font-size:16px;font-weight:800;">' . e($item->voucher_pin) . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height:12px;"></td></tr>
            ';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Result Checker PIN</title>
        </head>
        <body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:30px 12px;">
                <tr>
                    <td align="center">
                        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #e5e7eb;">
                            <tr>
                                <td style="padding:28px;background:linear-gradient(135deg,#2563eb,#16a34a);color:#ffffff;">
                                    <h1 style="margin:0;font-size:24px;line-height:1.25;">Your Result Checker PIN is Ready</h1>
                                    <p style="margin:8px 0 0;font-size:14px;opacity:.9;">Thank you for using KlikDataHub.</p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:28px;">
                                    <p style="margin:0 0 18px;color:#0f172a;font-size:15px;line-height:1.7;">
                                        Dear <strong>' . e($order->customer_name ?: 'Customer') . '</strong>, your <strong>' . e($order->checker_name) . '</strong> order has been completed successfully.
                                    </p>

                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
                                        <tr>
                                            <td style="padding:14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:14px;">
                                                <p style="margin:0;color:#64748b;font-size:12px;">Order Number</p>
                                                <p style="margin:6px 0 0;color:#020617;font-size:16px;font-weight:800;">' . e($order->order_number) . '</p>
                                            </td>
                                            <td width="12"></td>
                                            <td style="padding:14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:14px;">
                                                <p style="margin:0;color:#64748b;font-size:12px;">Amount Paid</p>
                                                <p style="margin:6px 0 0;color:#020617;font-size:16px;font-weight:800;">' . e($order->currency) . ' ' . number_format((float) $order->total_amount, 2) . '</p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        ' . $voucherHtml . '
                                    </table>

                                    <a href="https://ghana.waecdirect.org/" style="display:block;margin-top:18px;padding:15px 18px;background:#16a34a;color:#ffffff;text-decoration:none;text-align:center;border-radius:14px;font-weight:800;">
                                        Open WAECDirect Result Portal
                                    </a>

                                    <p style="margin:24px 0 0;color:#64748b;font-size:13px;line-height:1.7;">
                                        Keep this email safe. Do not share your PIN with anyone you do not trust.
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:18px 28px;background:#f8fafc;color:#64748b;font-size:12px;text-align:center;">
                                    © ' . date('Y') . ' KlikDataHub. All rights reserved.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    private function deliverySmsMessage(object $order, $items): string
    {
        $pins = [];

        foreach ($items as $index => $item) {
            $pins[] = 'V' . ($index + 1) . ': Serial ' . $item->serial_number . ', PIN ' . $item->voucher_pin;
        }

        return 'KlikDataHub ' . $order->checker_name . ' ' . $order->order_number . '. ' . implode(' | ', $pins);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'O' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (
            DB::table('result_checker_orders')
                ->where('order_number', $number)
                ->exists()
        );

        return $number;
    }

    private function generatePaymentReference(string $prefix = 'P'): string
    {
        do {
            $reference = $prefix . now()->format('ymd') . strtoupper(Str::random(5));
        } while (
            DB::table('result_checker_payments')
                ->where('payment_reference', $reference)
                ->exists()
        );

        return $reference;
    }

    private function paystackCharge(float $amount): float
    {
        return round($amount * $this->paystackChargeRate, 2);
    }

    private function toSubunit(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function currency(): string
    {
        return strtoupper(env('PAYSTACK_CURRENCY', 'GHS'));
    }
}