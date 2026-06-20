<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AfaHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('user')->user();

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $afaOrders = DB::table('afa_orders')
            ->leftJoin('afa_payments', 'afa_payments.order_id', '=', 'afa_orders.order_id')
            ->where('afa_orders.user_id', $user->user_id)
            ->when($search, function ($query) use ($search) {
                $cleanPhone = preg_replace('/\D+/', '', $search);

                $query->where(function ($subQuery) use ($search, $cleanPhone) {
                    $subQuery->where('afa_orders.order_reference', 'LIKE', "%{$search}%")
                        ->orWhere('afa_orders.registration_name', 'LIKE', "%{$search}%")
                        ->orWhere('afa_orders.customer_name', 'LIKE', "%{$search}%")
                        ->orWhere('afa_orders.customer_email', 'LIKE', "%{$search}%")
                        ->orWhere('afa_orders.ghana_card_number', 'LIKE', "%{$search}%")
                        ->orWhere('afa_orders.location', 'LIKE', "%{$search}%")
                        ->orWhere('afa_orders.occupation', 'LIKE', "%{$search}%")
                        ->orWhere('afa_payments.payment_reference', 'LIKE', "%{$search}%");

                    if ($cleanPhone) {
                        $subQuery->orWhere('afa_orders.phone_number', 'LIKE', "%{$cleanPhone}%");
                    }
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('afa_orders.status', $status);
            })
            ->select([
                'afa_orders.order_id',
                'afa_orders.user_id',
                'afa_orders.afa_registration_id',
                'afa_orders.order_reference',
                'afa_orders.registration_name',
                'afa_orders.amount as order_amount',
                'afa_orders.customer_name',
                'afa_orders.customer_email',
                'afa_orders.phone_number',
                'afa_orders.ghana_card_number',
                'afa_orders.location',
                'afa_orders.occupation',
                'afa_orders.status',
                'afa_orders.admin_notes',
                'afa_orders.ordered_at',
                'afa_orders.processing_at',
                'afa_orders.completed_at',
                'afa_orders.cancelled_at',
                'afa_orders.created_at',
                'afa_orders.updated_at',

                'afa_payments.payment_reference',
                'afa_payments.payment_method',
                'afa_payments.amount as payment_amount',
                'afa_payments.charges',
                'afa_payments.status as payment_status',
                'afa_payments.channel',
                'afa_payments.fees',
                'afa_payments.notes',
                'afa_payments.paid_at',
            ])
            ->orderByDesc('afa_orders.created_at')
            ->paginate(15)
            ->withQueryString();

        $afaOrders->getCollection()->transform(function ($order) {
            $order->actual_amount = $this->getActualAmount($order);
            $order->paid_amount = (float) ($order->payment_amount ?? $order->order_amount ?? 0);
            $order->charges_amount = round(max($order->paid_amount - $order->actual_amount, 0), 2);

            return $order;
        });

        return view('users.afa.history', compact('afaOrders', 'search', 'status'));
    }

    private function getActualAmount(object $order): float
    {
        $notes = json_decode($order->notes ?? '', true);

        if (is_array($notes)) {
            if (isset($notes['base_amount']) && is_numeric($notes['base_amount'])) {
                return round((float) $notes['base_amount'], 2);
            }

            if (isset($notes['actual_amount']) && is_numeric($notes['actual_amount'])) {
                return round((float) $notes['actual_amount'], 2);
            }

            if (isset($notes['registration_amount']) && is_numeric($notes['registration_amount'])) {
                return round((float) $notes['registration_amount'], 2);
            }
        }

        $paidAmount = (float) ($order->payment_amount ?? $order->order_amount ?? 0);
        $charges = (float) ($order->charges ?? 0);

        if ($charges > 0 && $paidAmount > $charges) {
            return round($paidAmount - $charges, 2);
        }

        return round((float) ($order->order_amount ?? 0), 2);
    }
}