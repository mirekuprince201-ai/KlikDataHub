<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.finance.transactions', [
            'summary' => $this->summary($request),
        ]);
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $search = strtolower($request->input('search.value', ''));

        $transactions = $this->transactions($request);

        $recordsTotal = $transactions->count();

        if ($search !== '') {
            $transactions = $transactions->filter(function ($item) use ($search) {
                return str_contains(strtolower($item->user_name ?? ''), $search)
                    || str_contains(strtolower($item->phone ?? ''), $search)
                    || str_contains(strtolower($item->email ?? ''), $search)
                    || str_contains(strtolower($item->type ?? ''), $search)
                    || str_contains(strtolower($item->reference ?? ''), $search)
                    || str_contains(strtolower($item->method ?? ''), $search)
                    || str_contains(strtolower($item->status ?? ''), $search);
            })->values();
        }

        $recordsFiltered = $transactions->count();

        $data = $transactions
            ->slice($start, $length)
            ->values()
            ->map(function ($transaction, $index) use ($start) {
                $typeClass = str_replace([' ', '_'], '-', strtolower($transaction->type ?? 'other'));
                $statusClass = str_replace([' ', '_'], '-', strtolower($transaction->status ?? 'pending'));

                return [
                    'row_number' => $start + $index + 1,

                    'user' => '
                        <strong>' . e(strtoupper($transaction->user_name ?? 'N/A')) . '</strong>
                        <small>' . e($transaction->phone ?? $transaction->email ?? 'No contact') . '</small>
                    ',

                    'type' => '
                        <span class="kd-type ' . e($typeClass) . '">' . e(strtoupper($transaction->type ?? 'OTHER')) . '</span>
                    ',

                    'reference' => e($transaction->reference ?? 'N/A'),

                    'method' => e(strtoupper($transaction->method ?? 'N/A')),

                    'amount' => '<strong>GHS ' . number_format((float) ($transaction->amount ?? 0), 2) . '</strong>',

                    'status' => '
                        <span class="kd-status ' . e($statusClass) . '">' . e(strtoupper($transaction->status ?? 'PENDING')) . '</span>
                    ',

                    'date' => $transaction->created_at
                        ? Carbon::parse($transaction->created_at)->format('d M, Y h:i A')
                        : 'N/A',
                ];
            });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function transactions(Request $request)
    {
        $fromDateInput = $request->input('from_date', '');
        $toDateInput = $request->input('to_date', '');
        $typeFilter = $request->input('type', 'all');
        $statusFilter = $request->input('status', 'all');

        $fromDate = $fromDateInput ? Carbon::parse($fromDateInput)->startOfDay() : null;
        $toDate = $toDateInput ? Carbon::parse($toDateInput)->endOfDay() : null;

        $transactions = collect();

        if (Schema::hasTable('wallet_transactions') && in_array($typeFilter, ['all', 'wallet', 'topup'])) {
            $query = DB::table('wallet_transactions')
                ->leftJoin('users', 'wallet_transactions.user_id', '=', 'users.user_id')
                ->select([
                    DB::raw("wallet_transactions.wallet_transaction_id as id"),
                    DB::raw("CASE WHEN wallet_transactions.source = 'wallet_topup' THEN 'TopUp' ELSE 'Wallet' END as type"),
                    DB::raw("wallet_transactions.transaction_reference as reference"),
                    DB::raw("COALESCE(wallet_transactions.source, wallet_transactions.transaction_type, 'Wallet') as method"),
                    DB::raw("COALESCE(wallet_transactions.amount, 0) as amount"),
                    DB::raw("COALESCE(wallet_transactions.status, 'pending') as status"),
                    DB::raw("COALESCE(users.full_name, CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')), 'N/A') as user_name"),
                    'users.phone',
                    'users.email',
                    'wallet_transactions.created_at',
                ]);

            $this->applyDateFilter($query, 'wallet_transactions.created_at', $fromDate, $toDate);
            $transactions = $transactions->merge($query->get());
        }

        if (Schema::hasTable('wallet_topups') && in_array($typeFilter, ['all', 'topup'])) {
            $query = DB::table('wallet_topups')
                ->leftJoin('users', 'wallet_topups.user_id', '=', 'users.user_id')
                ->select([
                    DB::raw("wallet_topups.topup_id as id"),
                    DB::raw("'TopUp' as type"),
                    DB::raw("COALESCE(wallet_topups.topup_reference, wallet_topups.payment_reference, wallet_topups.provider_reference, wallet_topups.topup_id) as reference"),
                    DB::raw("COALESCE(wallet_topups.payment_method, wallet_topups.provider, wallet_topups.channel, 'Wallet Topup') as method"),
                    DB::raw("COALESCE(wallet_topups.total_amount, wallet_topups.amount, 0) as amount"),
                    DB::raw("COALESCE(wallet_topups.status, 'pending') as status"),
                    DB::raw("COALESCE(users.full_name, CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')), wallet_topups.customer_email, 'N/A') as user_name"),
                    'users.phone',
                    DB::raw("COALESCE(users.email, wallet_topups.customer_email) as email"),
                    'wallet_topups.created_at',
                ]);

            $this->applyDateFilter($query, 'wallet_topups.created_at', $fromDate, $toDate);
            $transactions = $transactions->merge($query->get());
        }

        if (Schema::hasTable('afa_orders') && in_array($typeFilter, ['all', 'afa'])) {
            $query = DB::table('afa_orders')
                ->leftJoin('users', 'afa_orders.user_id', '=', 'users.user_id')
                ->select([
                    DB::raw("afa_orders.order_id as id"),
                    DB::raw("'AFA' as type"),
                    DB::raw("COALESCE(afa_orders.order_reference, afa_orders.order_id) as reference"),
                    DB::raw("COALESCE(afa_orders.registration_name, 'AFA Order') as method"),
                    DB::raw("COALESCE(afa_orders.amount, 0) as amount"),
                    DB::raw("COALESCE(afa_orders.status, 'pending') as status"),
                    DB::raw("COALESCE(afa_orders.customer_name, users.full_name, CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')), 'N/A') as user_name"),
                    DB::raw("COALESCE(afa_orders.phone_number, users.phone) as phone"),
                    DB::raw("COALESCE(afa_orders.customer_email, users.email) as email"),
                    'afa_orders.created_at',
                ]);

            $this->applyDateFilter($query, 'afa_orders.created_at', $fromDate, $toDate);
            $transactions = $transactions->merge($query->get());
        }

        if (Schema::hasTable('afa_payments') && in_array($typeFilter, ['all', 'afa'])) {
            $query = DB::table('afa_payments')
                ->leftJoin('users', 'afa_payments.user_id', '=', 'users.user_id')
                ->leftJoin('afa_orders', 'afa_payments.order_id', '=', 'afa_orders.order_id')
                ->select([
                    DB::raw("afa_payments.payment_id as id"),
                    DB::raw("'AFA Payment' as type"),
                    DB::raw("COALESCE(afa_payments.payment_reference, afa_payments.provider_reference, afa_payments.payment_id) as reference"),
                    DB::raw("COALESCE(afa_payments.payment_method, afa_payments.provider, afa_payments.channel, 'AFA Payment') as method"),
                    DB::raw("COALESCE(afa_payments.amount, 0) as amount"),
                    DB::raw("COALESCE(afa_payments.status, 'pending') as status"),
                    DB::raw("COALESCE(afa_orders.customer_name, users.full_name, CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')), 'N/A') as user_name"),
                    DB::raw("COALESCE(afa_orders.phone_number, users.phone) as phone"),
                    DB::raw("COALESCE(afa_payments.customer_email, afa_orders.customer_email, users.email) as email"),
                    'afa_payments.created_at',
                ]);

            $this->applyDateFilter($query, 'afa_payments.created_at', $fromDate, $toDate);
            $transactions = $transactions->merge($query->get());
        }

        if (Schema::hasTable('payments') && in_array($typeFilter, ['all', 'order'])) {
            $query = DB::table('payments')
                ->leftJoin('users', 'payments.user_id', '=', 'users.user_id')
                ->select([
                    DB::raw("payments.payment_id as id"),
                    DB::raw("'Order' as type"),
                    DB::raw("COALESCE(payments.payment_reference, payments.provider_reference, payments.payment_id) as reference"),
                    DB::raw("COALESCE(payments.payment_method, payments.provider, payments.channel, 'Order Payment') as method"),
                    DB::raw("COALESCE(payments.amount, 0) as amount"),
                    DB::raw("COALESCE(payments.status, 'pending') as status"),
                    DB::raw("COALESCE(users.full_name, CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')), payments.customer_email, 'N/A') as user_name"),
                    'users.phone',
                    DB::raw("COALESCE(users.email, payments.customer_email) as email"),
                    'payments.created_at',
                ]);

            $this->applyDateFilter($query, 'payments.created_at', $fromDate, $toDate);
            $transactions = $transactions->merge($query->get());
        }

        if ($statusFilter !== 'all') {
            $transactions = $transactions->filter(function ($transaction) use ($statusFilter) {
                return strtolower($transaction->status ?? '') === strtolower($statusFilter);
            });
        }

        return $transactions
            ->unique(fn ($transaction) => strtolower(($transaction->type ?? 'unknown') . '-' . ($transaction->reference ?? $transaction->id)))
            ->sortByDesc('created_at')
            ->values();
    }

    private function summary(Request $request): array
    {
        $transactions = $this->transactions($request);

        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'successful' => $transactions->filter(fn ($t) => in_array(strtolower($t->status ?? ''), ['success', 'successful', 'paid', 'completed', 'approved']))->count(),
            'pending' => $transactions->filter(fn ($t) => in_array(strtolower($t->status ?? ''), ['pending', 'processing', 'in_progress', 'in progress']))->count(),
        ];
    }

    private function applyDateFilter($query, string $column, $fromDate, $toDate): void
    {
        if ($fromDate && $toDate) {
            $query->whereBetween($column, [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->where($column, '>=', $fromDate);
        } elseif ($toDate) {
            $query->where($column, '<=', $toDate);
        }
    }
}