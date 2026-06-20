<?php

namespace App\Http\Controllers\Admin\AfaOrders;

use App\Http\Controllers\Controller;
use App\Models\AfaOrder;

class BaseAfaOrderController extends Controller
{
    protected function ordersByStatus(string $status)
    {
        return AfaOrder::with('payment')
            ->where('status', $status)
            ->latest('created_at')
            ->get();
    }

    protected function view(string $status, string $title)
    {
        $orders = $this->ordersByStatus($status);

        return view('admin.afa-orders.index', compact('orders', 'status', 'title'));
    }
}