<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TopUpController extends Controller
{
    public function index()
    {
        $users = DB::table('users')
            ->select([
                'user_id',
                'first_name',
                'last_name',
                'full_name',
                'phone',
                'email',
                'wallet_balance',
                'account_status',
                'created_at',
            ])
            ->orderBy('full_name')
            ->get();

        $transactions = DB::table('wallet_transactions')
            ->leftJoin('users', 'wallet_transactions.user_id', '=', 'users.user_id')
            ->select([
                'wallet_transactions.*',
                'users.full_name',
                'users.phone',
                'users.email',
            ])
            ->where('wallet_transactions.source', 'admin_adjustment')
            ->orderByDesc('wallet_transactions.created_at')
            ->get();

        return view('admin.finance.topups.index', [
            'users' => $users,
            'transactions' => $transactions,
            'totalUsers' => $users->count(),
            'totalWallet' => $users->sum(fn ($user) => (float) ($user->wallet_balance ?? 0)),
            'totalCredits' => $transactions->where('transaction_type', 'credit')->sum(fn ($txn) => (float) $txn->amount),
            'totalDebits' => $transactions->where('transaction_type', 'debit')->sum(fn ($txn) => (float) $txn->amount),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,user_id'],
            'transaction_type' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $type = strtolower($validated['transaction_type']);
        $description = trim((string) ($validated['description'] ?? ''));

        try {
            $result = DB::transaction(function () use ($validated, $amount, $type, $description) {
                $user = DB::table('users')
                    ->where('user_id', $validated['user_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    throw new \RuntimeException('User not found.');
                }

                if (strtolower((string) ($user->account_status ?? '')) !== 'active') {
                    throw new \RuntimeException('Only active users can receive wallet adjustments.');
                }

                $duplicateExists = DB::table('wallet_transactions')
                    ->where('user_id', $user->user_id)
                    ->where('transaction_type', $type)
                    ->where('source', 'admin_adjustment')
                    ->where('amount', $amount)
                    ->where('status', 'successful')
                    ->where('created_at', '>=', now()->subSeconds(45))
                    ->exists();

                if ($duplicateExists) {
                    throw new \RuntimeException('Duplicate transaction blocked. Please wait before submitting the same adjustment again.');
                }

                $balanceBefore = round((float) ($user->wallet_balance ?? 0), 2);

                if ($type === 'debit' && $amount > $balanceBefore) {
                    throw new \RuntimeException('Insufficient wallet balance. You cannot debit more than the user balance.');
                }

                $balanceAfter = $type === 'credit'
                    ? $balanceBefore + $amount
                    : $balanceBefore - $amount;

                $balanceAfter = round($balanceAfter, 2);

                $reference = $this->generateTransactionReference();

                DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->update([
                        'wallet_balance' => $balanceAfter,
                        'updated_at' => now(),
                    ]);

                DB::table('wallet_transactions')->insert([
                    'wallet_transaction_id' => (string) Str::uuid(),
                    'user_id' => $user->user_id,
                    'transaction_reference' => $reference,
                    'transaction_type' => $type,
                    'source' => 'admin_adjustment',
                    'source_id' => null,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => $description ?: ($type === 'credit' ? 'Wallet credited by admin' : 'Wallet debited by admin'),
                    'status' => 'successful',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [
                    'user' => $user,
                    'reference' => $reference,
                    'type' => $type,
                    'amount' => $amount,
                    'balance_after' => $balanceAfter,
                ];
            });

            $this->sendWalletSmsAlert(
                $result['user'],
                $result['type'],
                $result['amount'],
                $result['balance_after'],
                $result['reference']
            );

            return back()->with(
                'success',
                $type === 'credit'
                    ? 'User wallet credited successfully. SMS alert sent if phone number is valid.'
                    : 'User wallet debited successfully. SMS alert sent if phone number is valid.'
            );
        } catch (\Throwable $e) {
            Log::warning('Admin wallet adjustment failed', [
                'user_id' => $validated['user_id'] ?? null,
                'type' => $type ?? null,
                'amount' => $amount ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    private function sendWalletSmsAlert(object $user, string $type, float $amount, float $balanceAfter, string $reference): void
    {
        $phone = $this->normalizeGhanaPhone($user->phone ?? null);

        if (!$phone) {
            Log::info('Wallet SMS skipped: invalid user phone', [
                'user_id' => $user->user_id ?? null,
                'phone' => $user->phone ?? null,
            ]);

            return;
        }

        $apiBase = rtrim((string) env('SMSNOTIFY_API_BASE'), '/');
        $apiKey = (string) env('SMSNOTIFY_API_KEY');
        $senderId = (string) env('SMS_SENDER_ID', 'KLIKDATAHUB');
        $fakeSms = filter_var(env('FAKE_SMS', false), FILTER_VALIDATE_BOOLEAN);

        if (!$apiBase || !$apiKey || !$senderId) {
            Log::warning('Wallet SMS skipped: SMSNotify configuration missing.');
            return;
        }

        $name = $this->displayName($user);
        $action = $type === 'credit' ? 'credited' : 'debited';

        $message = "KLIKDATAHUB Alert: Dear {$name}, your wallet has been {$action} with GHS "
            . number_format($amount, 2)
            . ". Balance: GHS "
            . number_format($balanceAfter, 2)
            . ". Ref: {$reference}.";

        if ($fakeSms) {
            Log::info('FAKE Wallet SMS', [
                'to' => $phone,
                'message' => $message,
            ]);

            return;
        }

        try {
            $response = Http::timeout(15)
                ->retry(2, 500)
                ->asForm()
                ->post($apiBase, [
                    'key' => $apiKey,
                    'to' => $phone,
                    'msg' => $message,
                    'sender_id' => $senderId,
                ]);

            if (!$response->successful()) {
                Log::warning('Wallet SMS failed', [
                    'user_id' => $user->user_id ?? null,
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return;
            }

            Log::info('Wallet SMS sent successfully', [
                'user_id' => $user->user_id ?? null,
                'phone' => $phone,
                'reference' => $reference,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Wallet SMS exception', [
                'user_id' => $user->user_id ?? null,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeGhanaPhone(?string $phone): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            return '233' . substr($phone, 1);
        }

        if (strlen($phone) === 12 && str_starts_with($phone, '233')) {
            return $phone;
        }

        return null;
    }

    private function displayName(object $user): string
    {
        $name = trim((string) ($user->full_name ?? ''));

        if ($name === '') {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }

        return strtoupper($name ?: 'USER');
    }

    private function generateTransactionReference(): string
    {
        do {
            $reference = 'ADM-' . strtoupper(Str::random(10));
        } while (
            DB::table('wallet_transactions')
                ->where('transaction_reference', $reference)
                ->exists()
        );

        return $reference;
    }
}