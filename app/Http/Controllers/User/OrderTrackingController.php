<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $orders = collect();
        $searched = $search !== '';

        if ($searched) {
            $orders = $this->searchOrders($search);
        }

        return view('users.orders.track', [
            'orders' => $orders,
            'searched' => $searched,
            'search' => $search,
        ]);
    }

    private function searchOrders(string $search)
    {
        $cleanPhone = preg_replace('/\D+/', '', $search);
        $email = strtolower(trim($search));
        $reference = trim($search);

        return DB::table('orders')
            ->leftJoin('payments', 'payments.order_id', '=', 'orders.order_id')
            ->where(function ($query) use ($reference, $cleanPhone, $email) {
                $query->where('orders.order_reference', $reference)
                    ->orWhere('payments.payment_reference', $reference)
                    ->orWhere('payments.provider_reference', $reference);

                if ($cleanPhone !== '') {
                    $query->orWhere('orders.beneficiary_number', $cleanPhone);
                }

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $query->orWhere('orders.customer_email', $email);
                }
            })
            ->select($this->selectColumns())
            ->orderByDesc('orders.created_at')
            ->limit(10)
            ->get()
            ->map(fn ($order) => $this->decorateOrder($order));
    }

    private function selectColumns(): array
    {
        return [
            'orders.order_id',
            'orders.user_id',
            'orders.order_reference',
            'orders.network_name',
            'orders.plan_name',
            'orders.data_size',
            'orders.beneficiary_number',
            'orders.customer_email',
            'orders.amount as order_amount',
            'orders.is_ported',
            'orders.status as order_status',
            'orders.admin_notes',
            'orders.ordered_at',
            'orders.processing_at',
            'orders.delivered_at',
            'orders.cancelled_at',
            'orders.created_at as order_created_at',
            'orders.updated_at as order_updated_at',

            'payments.payment_id',
            'payments.payment_reference',
            'payments.payment_method',
            'payments.amount as payment_amount',
            'payments.amount_subunit',
            'payments.charges',
            'payments.currency',
            'payments.status as payment_status',
            'payments.provider',
            'payments.provider_reference',
            'payments.provider_transaction_id',
            'payments.gateway_response',
            'payments.channel',
            'payments.fees',
            'payments.customer_email as payment_customer_email',
            'payments.paid_at',
            'payments.verified_at',
            'payments.webhook_received_at',
        ];
    }

    private function decorateOrder($order)
    {
        $orderStatus = strtolower((string) ($order->order_status ?? 'pending'));
        $paymentStatus = strtolower((string) ($order->payment_status ?? 'pending'));

        $order->status_label = $this->statusLabel($orderStatus);
        $order->status_class = $this->statusClass($orderStatus);

        $order->payment_status_label = $this->statusLabel($paymentStatus);
        $order->payment_status_class = $this->statusClass($paymentStatus);

        $order->main_message = $this->mainMessage($orderStatus);
        $order->status_description = $this->statusDescription($orderStatus);

        $order->timeline = $this->timeline($order, $orderStatus);

        return $order;
    }

    private function timeline($order, string $status): array
    {
        $timeline = [
            [
                'key' => 'received',
                'title' => 'Order Received',
                'description' => 'Your order has been received successfully.',
                'time' => $order->ordered_at,
                'done' => true,
                'active' => $status === 'pending',
            ],
            [
                'key' => 'processing',
                'title' => 'Processing',
                'description' => 'Your order is being checked and prepared.',
                'time' => $order->processing_at,
                'done' => in_array($status, ['processing', 'in_progress', 'delivered'], true),
                'active' => $status === 'processing',
            ],
            [
                'key' => 'in_progress',
                'title' => 'In Progress',
                'description' => 'Your bundle is currently being worked on.',
                'time' => $order->processing_at,
                'done' => in_array($status, ['in_progress', 'delivered'], true),
                'active' => $status === 'in_progress',
            ],
            [
                'key' => 'delivered',
                'title' => 'Delivered',
                'description' => 'Your data bundle has been delivered.',
                'time' => $order->delivered_at,
                'done' => $status === 'delivered',
                'active' => $status === 'delivered',
            ],
        ];

        if ($status === 'cancelled') {
            $timeline[] = [
                'key' => 'cancelled',
                'title' => 'Cancelled',
                'description' => 'This order was cancelled.',
                'time' => $order->cancelled_at,
                'done' => true,
                'active' => true,
            ];
        }

        if ($status === 'failed') {
            $timeline[] = [
                'key' => 'failed',
                'title' => 'Failed',
                'description' => 'This order could not be completed.',
                'time' => $order->order_updated_at,
                'done' => true,
                'active' => true,
            ];
        }

        return $timeline;
    }

    private function statusLabel(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'successful' => 'Successful',
            'initialized' => 'Initialized',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'in_progress' => 'In Progress',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status ?: 'Pending')),
        };
    }

    private function statusClass(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'successful', 'delivered' => 'success',
            'processing' => 'processing',
            'in_progress' => 'progress',
            'pending', 'initialized' => 'pending',
            'failed', 'cancelled' => 'danger',
            default => 'pending',
        };
    }

private function mainMessage(string $status): string
{
    return match ($status) {
        'pending' => 'Payment has not been completed for this order.',
        'processing' => 'Payment confirmed. Your order is currently processing.',
        'in_progress' => 'Your order is in progress. Please wait while we complete delivery.',
        'delivered' => 'Your order has been delivered successfully.',
        'cancelled' => 'This order has been cancelled.',
        'failed' => 'This order could not be completed.',
        default => 'Your order status is available below.',
    };
}

private function statusDescription(string $status): string
{
    return match ($status) {
        'pending' => 'This order is not yet active because payment has not been received or verified.',
        'processing' => 'Your payment has been confirmed and your order is being prepared for delivery.',
        'in_progress' => 'Delivery is ongoing. Please check again shortly for the final update.',
        'delivered' => 'The requested data bundle has been sent to the beneficiary number.',
        'cancelled' => 'Please contact support if you believe this was a mistake.',
        'failed' => 'Please contact support with your order reference for assistance.',
        default => 'Thank you for using KlikDataHub.',
    };
}
}