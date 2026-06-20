<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PendingOrderController extends Controller
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
            ->where('orders.status', 'pending')
            ->orderByDesc(DB::raw('COALESCE(orders.ordered_at, orders.created_at)'))
            ->get();

        $totalOrders = $orders->count();

        $totalAmount = $orders->sum(function ($order) {
            return (float) ($order->amount ?? 0);
        });

        $totalData = $orders->sum(function ($order) {
            $dataSize = strtoupper($order->data_size ?? '0');
            return (float) preg_replace('/[^0-9.]/', '', $dataSize);
        });

        $paidOrders = $orders->filter(function ($order) {
            return strtolower($order->payment_status ?? '') === 'successful';
        })->count();

        $unpaidOrders = $orders->filter(function ($order) {
            return strtolower($order->payment_status ?? '') !== 'successful';
        })->count();

        return view('admin.orders.pending', compact(
            'orders',
            'totalOrders',
            'totalAmount',
            'totalData',
            'paidOrders',
            'unpaidOrders'
        ));
    }
}