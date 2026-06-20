<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('user')->user();

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $orders = DB::table('orders')
            ->leftJoin('payments', 'payments.order_id', '=', 'orders.order_id')
            ->when($user, function ($query) use ($user) {
                $query->where('orders.user_id', $user->user_id);
            })
            ->when(!$user, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($search, function ($query) use ($search) {
                $cleanPhone = preg_replace('/\D+/', '', $search);

                $query->where(function ($subQuery) use ($search, $cleanPhone) {
                    $subQuery->where('orders.order_reference', 'LIKE', "%{$search}%")
                        ->orWhere('orders.network_name', 'LIKE', "%{$search}%")
                        ->orWhere('orders.plan_name', 'LIKE', "%{$search}%")
                        ->orWhere('orders.data_size', 'LIKE', "%{$search}%")
                        ->orWhere('orders.customer_email', 'LIKE', "%{$search}%")
                        ->orWhere('payments.payment_reference', 'LIKE', "%{$search}%");

                    if ($cleanPhone) {
                        $subQuery->orWhere('orders.beneficiary_number', 'LIKE', "%{$cleanPhone}%");
                    }
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('orders.status', $status);
            })
            ->select([
                'orders.*',
                'payments.payment_reference',
                'payments.payment_method',
                'payments.status as payment_status',
                'payments.channel',
                'payments.paid_at',
            ])
            ->orderByDesc('orders.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('users.orders.index', compact('orders', 'search', 'status'));
    }
}