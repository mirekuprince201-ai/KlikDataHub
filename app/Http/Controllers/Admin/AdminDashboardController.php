<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $dateFilter = $request->get('date_filter', '30');
        $status = $request->get('status', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        [$from, $to, $days] = $this->resolveDateRange($dateFilter, $fromDate, $toDate);

        $dataOrdersQuery = DB::table('orders')->whereBetween('created_at', [$from, $to]);
        $afaOrdersQuery = DB::table('afa_orders')->whereBetween('created_at', [$from, $to]);
        $paymentsQuery = DB::table('payments')->whereBetween('created_at', [$from, $to]);
        $afaPaymentsQuery = DB::table('afa_payments')->whereBetween('created_at', [$from, $to]);
        $walletTopupsQuery = DB::table('wallet_topups')->whereBetween('created_at', [$from, $to]);

        if ($status !== 'all') {
            $dataOrdersQuery->where('status', $status);
            $afaOrdersQuery->where('status', $status);
        }

        $successfulPaymentsAmount = (clone $paymentsQuery)->where('status', 'successful')->sum('amount');
        $successfulAfaPaymentsAmount = (clone $afaPaymentsQuery)->where('status', 'successful')->sum('amount');
        $successfulWalletTopupsAmount = (clone $walletTopupsQuery)->where('status', 'successful')->sum('amount');

        $stats = [
            'total_admins' => Admin::count(),
            'active_admins' => Admin::where('status', 'Active')->count(),
            'inactive_admins' => Admin::where('status', 'Inactive')->count(),
            'suspended_admins' => Admin::where('status', 'Suspended')->count(),

            'total_users' => DB::table('users')->count(),
            'active_users' => DB::table('users')->where('account_status', 'active')->count(),
            'wallet_balance' => DB::table('users')->sum('wallet_balance'),

            'data_orders' => (clone $dataOrdersQuery)->count(),
            'afa_orders' => (clone $afaOrdersQuery)->count(),
            'total_orders' => (clone $dataOrdersQuery)->count() + (clone $afaOrdersQuery)->count(),

            'pending_orders' => $this->countOrdersByStatus('pending', $from, $to),
            'processing_orders' => $this->countOrdersByStatus('processing', $from, $to),
            'in_progress_orders' => $this->countOrdersByStatus('in_progress', $from, $to),

            'delivered_orders' => DB::table('orders')
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('status', ['delivered', 'completed'])
                    ->count()
                + DB::table('afa_orders')
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('status', ['delivered', 'completed'])
                    ->count(),

            'cancelled_orders' => $this->countOrdersByStatus('cancelled', $from, $to),

            'revenue' => $successfulPaymentsAmount + $successfulAfaPaymentsAmount,
            'wallet_topups' => $successfulWalletTopupsAmount,

            'payment_fees' => DB::table('payments')
                    ->whereBetween('created_at', [$from, $to])
                    ->where('status', 'successful')
                    ->sum('fees')
                + DB::table('afa_payments')
                    ->whereBetween('created_at', [$from, $to])
                    ->where('status', 'successful')
                    ->sum('fees'),
        ];

        $dailyLabels = [];
        $dailyDataOrders = [];
        $dailyAfaOrders = [];
        $dailyRevenue = [];

        $period = Carbon::parse($from)->startOfDay();
        $endPeriod = Carbon::parse($to)->startOfDay();

        while ($period->lte($endPeriod)) {
            $date = $period->toDateString();

            $dailyLabels[] = $period->format('M d');

            $dailyDataOrders[] = DB::table('orders')
                ->whereDate('created_at', $date)
                ->count();

            $dailyAfaOrders[] = DB::table('afa_orders')
                ->whereDate('created_at', $date)
                ->count();

            $dailyRevenue[] = DB::table('payments')
                    ->whereDate('created_at', $date)
                    ->where('status', 'successful')
                    ->sum('amount')
                + DB::table('afa_payments')
                    ->whereDate('created_at', $date)
                    ->where('status', 'successful')
                    ->sum('amount');

            $period->addDay();
        }

        $orderStatus = [
            'pending' => $this->countOrdersByStatus('pending', $from, $to),
            'processing' => $this->countOrdersByStatus('processing', $from, $to),
            'in_progress' => $this->countOrdersByStatus('in_progress', $from, $to),

            'delivered' => DB::table('orders')
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('status', ['delivered', 'completed'])
                    ->count()
                + DB::table('afa_orders')
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('status', ['delivered', 'completed'])
                    ->count(),

            'cancelled' => $this->countOrdersByStatus('cancelled', $from, $to),
        ];

        $networkOrders = DB::table('orders')
            ->select('network_name', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('network_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $recentDataOrders = DB::table('orders')
            ->select(
                'order_reference',
                'network_name',
                'plan_name',
                'beneficiary_number',
                'customer_email',
                'amount',
                'status',
                'created_at'
            )
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(8)
            ->get();

        $recentAfaOrders = DB::table('afa_orders')
            ->select(
                'order_reference',
                'registration_name',
                'customer_name',
                'phone_number',
                'customer_email',
                'amount',
                'status',
                'created_at'
            )
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(8)
            ->get();

        $recentWalletTransactions = DB::table('wallet_transactions')
            ->leftJoin('users', 'wallet_transactions.user_id', '=', 'users.user_id')
            ->select(
                'wallet_transactions.transaction_reference',
                'wallet_transactions.transaction_type',
                'wallet_transactions.amount',
                'wallet_transactions.balance_after',
                'wallet_transactions.description',
                'wallet_transactions.status',
                'wallet_transactions.created_at',
                'users.full_name'
            )
            ->whereBetween('wallet_transactions.created_at', [$from, $to])
            ->latest('wallet_transactions.created_at')
            ->limit(8)
            ->get();

        $chartData = [
            'labels' => $dailyLabels,
            'dataOrders' => $dailyDataOrders,
            'afaOrders' => $dailyAfaOrders,
            'revenue' => $dailyRevenue,

            'statusLabels' => ['Pending', 'Processing', 'In Progress', 'Delivered', 'Cancelled'],
            'statusData' => [
                $orderStatus['pending'],
                $orderStatus['processing'],
                $orderStatus['in_progress'],
                $orderStatus['delivered'],
                $orderStatus['cancelled'],
            ],

            'networkLabels' => $networkOrders->pluck('network_name')->toArray(),
            'networkData' => $networkOrders->pluck('total')->toArray(),
        ];

        return view('admin.dashboard.index', compact(
            'admin',
            'stats',
            'chartData',
            'recentDataOrders',
            'recentAfaOrders',
            'recentWalletTransactions',
            'dateFilter',
            'status',
            'fromDate',
            'toDate',
            'from',
            'to'
        ));
    }

    private function resolveDateRange($dateFilter, $fromDate = null, $toDate = null): array
    {
        if ($dateFilter === 'today') {
            return [now()->startOfDay(), now()->endOfDay(), 1];
        }

        if ($dateFilter === 'yesterday') {
            return [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                1
            ];
        }

        if ($dateFilter === 'this_month') {
            return [
                now()->startOfMonth(),
                now()->endOfMonth(),
                now()->daysInMonth
            ];
        }

        if ($dateFilter === 'custom' && $fromDate && $toDate) {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [
                $from,
                $to,
                max(1, $from->diffInDays($to) + 1)
            ];
        }

        $days = is_numeric($dateFilter) ? (int) $dateFilter : 30;

        return [
            now()->subDays($days - 1)->startOfDay(),
            now()->endOfDay(),
            $days
        ];
    }

    private function countOrdersByStatus(string $status, $from, $to): int
    {
        return DB::table('orders')
                ->whereBetween('created_at', [$from, $to])
                ->where('status', $status)
                ->count()
            + DB::table('afa_orders')
                ->whereBetween('created_at', [$from, $to])
                ->where('status', $status)
                ->count();
    }
}