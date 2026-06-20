<?php

namespace App\Http\Controllers\Admin\AfaOrders;

use App\Models\AfaOrder;
use Illuminate\Http\Request;

class InProgressAfaOrderController extends BaseAfaOrderController
{
    public function index()
    {
        return $this->view('in-progress', 'In-Progress AFA Orders');
    }

    public function complete(Request $request)
    {
        $request->validate([
            'order_id' => ['required', 'exists:afa_orders,order_id'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = AfaOrder::where('order_id', $request->order_id)
            ->where('status', 'in-progress')
            ->firstOrFail();

        $order->update([
            'status' => 'delivered',
            'admin_notes' => $request->admin_notes,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'AFA order completed successfully.');
    }

    public function fail(Request $request)
    {
        $request->validate([
            'order_id' => ['required', 'exists:afa_orders,order_id'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = AfaOrder::where('order_id', $request->order_id)
            ->where('status', 'in-progress')
            ->firstOrFail();

        $order->update([
            'status' => 'failed',
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'AFA order marked as failed.');
    }
}