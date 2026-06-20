<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DeliveredOrderController extends Controller
{
    public function index()
    {
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
            ->where('orders.status', 'delivered')
            ->orderByDesc(DB::raw('COALESCE(orders.delivered_at, orders.completed_at, orders.updated_at, orders.created_at)'))
            ->get();

        $totalOrders = $orders->count();

        $totalAmount = $orders->sum(function ($order) {
            return (float) ($order->amount ?? 0);
        });

        $totalData = $orders->sum(function ($order) {
            $dataSize = strtoupper($order->data_size ?? '0');
            return (float) preg_replace('/[^0-9.]/', '', $dataSize);
        });

        $autoCompletedOrders = $orders->filter(function ($order) {
            return (int) ($order->auto_completed ?? 0) === 1;
        })->count();

        $manualCompletedOrders = $orders->filter(function ($order) {
            return (int) ($order->auto_completed ?? 0) !== 1;
        })->count();

        return view('admin.orders.delivered', compact(
            'orders',
            'totalOrders',
            'totalAmount',
            'totalData',
            'autoCompletedOrders',
            'manualCompletedOrders'
        ));
    }
}