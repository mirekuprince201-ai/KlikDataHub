<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function pending()
    {
        return $this->loadOrders(
            'pending',
            'Pending Orders',
            'admin.orders.pending'
        );
    }

    public function processing()
    {
        return $this->loadOrders(
            'processing',
            'Processing Orders',
            'admin.orders.processing'
        );
    }

    public function delivered()
    {
        return $this->loadOrders(
            'delivered',
            'Delivered Orders',
            'admin.orders.delivered'
        );
    }

    public function cancelled()
    {
        return $this->loadOrders(
            'cancelled',
            'Cancelled Orders',
            'admin.orders.cancelled'
        );
    }

    private function loadOrders(
        string $status,
        string $pageTitle,
        string $view
    ) {
        $orders = DB::table('orders')
            ->leftJoin('payments', 'orders.order_id', '=', 'payments.order_id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.user_id')
            ->select(
                'orders.*',

                'payments.payment_reference',
                'payments.payment_method',
                'payments.provider',
                'payments.channel',
                'payments.status as payment_status',
                'payments.paid_at',

                'users.first_name',
                'users.last_name',
                'users.phone as user_phone'
            )
            ->where('orders.status', $status)
            ->orderByDesc('orders.created_at')
            ->get();

        return view($view, [
            'pageTitle'          => $pageTitle,
            'status'             => $status,
            'orders'             => $orders,
            'totalOrders'        => $orders->count(),
            'totalAmount'        => $orders->sum('amount'),
            'successfulPayments' => $orders->where('payment_status', 'successful')->count(),
            'failedPayments'     => $orders->where('payment_status', 'failed')->count(),
        ]);
    }
}