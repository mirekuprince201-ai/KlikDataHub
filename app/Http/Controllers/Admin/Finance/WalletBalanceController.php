<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletBalanceController extends Controller
{
    public function index()
    {
        $hasWalletTransactions = Schema::hasTable('wallet_transactions');
        $hasWalletTopups = Schema::hasTable('wallet_topups');

        $users = DB::table('users')
            ->select([
                'user_id',
                'full_name',
                'first_name',
                'last_name',
                'phone',
                'email',
                'wallet_balance',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('wallet_balance')
            ->get();

        $transactionSummaries = collect();
        $walletTransactions = collect();
        $walletTopups = collect();

        if ($hasWalletTransactions) {
            $transactionSummaries = DB::table('wallet_transactions')
                ->selectRaw("
                    user_id,
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit,
                    COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit
                ")
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $walletTransactions = DB::table('wallet_transactions')
                ->select([
                    'wallet_transaction_id',
                    'user_id',
                    'transaction_reference',
                    'transaction_type',
                    'source',
                    'source_id',
                    'amount',
                    'balance_before',
                    'balance_after',
                    'description',
                    'status',
                    'created_at',
                    'updated_at',
                ])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('user_id');
        }

        if ($hasWalletTopups) {
            $walletTopups = DB::table('wallet_topups')
                ->select([
                    'topup_id',
                    'user_id',
                    'topup_reference',
                    'payment_reference',
                    'amount',
                    'charges',
                    'total_amount',
                    'currency',
                    'payment_method',
                    'provider',
                    'status',
                    'provider_reference',
                    'provider_transaction_id',
                    'gateway_response',
                    'channel',
                    'fees',
                    'customer_email',
                    'initialized_at',
                    'paid_at',
                    'verified_at',
                    'created_at',
                    'updated_at',
                ])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('user_id');
        }

        $users = $users->map(function ($user) use (
            $transactionSummaries,
            $walletTransactions,
            $walletTopups
        ) {
            $user->display_name = strtoupper(
                trim($user->full_name ?: (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')))
            ) ?: 'CUSTOMER';

            $summary = $transactionSummaries->get($user->user_id);

            $user->total_credit = (float) ($summary->total_credit ?? 0);
            $user->total_debit = (float) ($summary->total_debit ?? 0);
            $user->total_transactions = (int) ($summary->total_transactions ?? 0);

            $transactionsHistory = $this->formatWalletTransactions(
                $walletTransactions->get($user->user_id, collect())
            );

            $topupsHistory = $this->formatWalletTopups(
                $walletTopups->get($user->user_id, collect())
            );

            $user->wallet_history = $transactionsHistory
                ->merge($topupsHistory)
                ->sortByDesc('sort_date')
                ->values()
                ->map(function ($item) {
                    unset($item['sort_date']);
                    return $item;
                });

            $user->successful_topups = $topupsHistory
                ->filter(fn ($item) => strtolower($item['status'] ?? '') === 'successful')
                ->count();

            $user->pending_topups = $topupsHistory
                ->filter(fn ($item) => strtolower($item['status'] ?? '') !== 'successful')
                ->count();

            return $user;
        });

        $totalUsers = $users->count();

        $totalWalletBalance = $users->sum(
            fn ($user) => (float) ($user->wallet_balance ?? 0)
        );

        $activeWallets = $users->filter(
            fn ($user) => (float) ($user->wallet_balance ?? 0) > 0
        )->count();

        $zeroWallets = $users->filter(
            fn ($user) => (float) ($user->wallet_balance ?? 0) <= 0
        )->count();

        $totalCredit = $users->sum(
            fn ($user) => (float) ($user->total_credit ?? 0)
        );

        $totalDebit = $users->sum(
            fn ($user) => (float) ($user->total_debit ?? 0)
        );

        return view('admin.finance.wallet-balances', compact(
            'users',
            'totalUsers',
            'totalWalletBalance',
            'activeWallets',
            'zeroWallets',
            'totalCredit',
            'totalDebit'
        ));
    }

    private function formatWalletTransactions(Collection $transactions): Collection
    {
        return $transactions->map(function ($transaction) {
            return [
                'record_type' => 'Wallet Transaction',
                'type' => strtolower($transaction->transaction_type ?? 'N/A'),
                'source' => $this->formatLabel($transaction->source ?? 'System'),
                'reference' => $transaction->transaction_reference ?? 'N/A',
                'amount' => (float) ($transaction->amount ?? 0),
                'charges' => 0,
                'total_amount' => (float) ($transaction->amount ?? 0),
                'balance_before' => (float) ($transaction->balance_before ?? 0),
                'balance_after' => (float) ($transaction->balance_after ?? 0),
                'description' => $transaction->description ?? 'Wallet transaction',
                'status' => $this->formatLabel($transaction->status ?? 'N/A'),
                'payment_method' => 'Wallet',
                'provider' => $this->formatLabel($transaction->source ?? 'System'),
                'channel' => 'N/A',
                'gateway_response' => 'N/A',
                'created_at' => $this->formatDateTime($transaction->created_at ?? null),
                'sort_date' => $transaction->created_at,
            ];
        });
    }

    private function formatWalletTopups(Collection $topups): Collection
    {
        return $topups->map(function ($topup) {
            return [
                'record_type' => 'Wallet Top-up',
                'type' => 'credit',
                'source' => 'Wallet Top-up',
                'reference' => $topup->topup_reference
                    ?? $topup->payment_reference
                    ?? $topup->provider_reference
                    ?? 'N/A',
                'amount' => (float) ($topup->amount ?? 0),
                'charges' => (float) ($topup->charges ?? $topup->fees ?? 0),
                'total_amount' => (float) ($topup->total_amount ?? $topup->amount ?? 0),
                'balance_before' => 0,
                'balance_after' => 0,
                'description' => 'Wallet top-up via ' . $this->formatLabel($topup->provider ?? 'Provider'),
                'status' => $this->formatLabel($topup->status ?? 'N/A'),
                'payment_method' => $this->formatLabel($topup->payment_method ?? 'N/A'),
                'provider' => $this->formatLabel($topup->provider ?? 'N/A'),
                'channel' => $this->formatLabel($topup->channel ?? 'N/A'),
                'gateway_response' => $topup->gateway_response ?? 'N/A',
                'created_at' => $this->formatDateTime($topup->created_at ?? null),
                'sort_date' => $topup->created_at,
            ];
        });
    }

    private function formatDateTime($date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            return Carbon::parse($date)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    private function formatLabel(?string $value): string
    {
        if (empty($value)) {
            return 'N/A';
        }

        return ucwords(str_replace(['_', '-'], ' ', strtolower($value)));
    }
}