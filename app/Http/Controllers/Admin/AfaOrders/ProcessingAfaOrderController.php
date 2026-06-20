<?php

namespace App\Http\Controllers\Admin\AfaOrders;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessingAfaOrderController extends Controller
{
    public function index()
    {
        $orders = DB::table('afa_orders')
            ->join('afa_payments', 'afa_orders.order_id', '=', 'afa_payments.order_id')
            ->leftJoin('users', 'afa_orders.user_id', '=', 'users.user_id')
            ->select(
                'afa_orders.order_id',
                'afa_orders.user_id',
                'afa_orders.afa_registration_id',
                'afa_orders.order_reference',
                'afa_orders.registration_name',
                'afa_orders.amount',
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

                'afa_payments.payment_id',
                'afa_payments.payment_reference',
                'afa_payments.payment_method',
                'afa_payments.currency',
                'afa_payments.status as payment_status',
                'afa_payments.provider',
                'afa_payments.provider_reference',
                'afa_payments.provider_transaction_id',
                'afa_payments.gateway_response',
                'afa_payments.channel',
                'afa_payments.customer_email as payment_customer_email',
                'afa_payments.paid_at',
                'afa_payments.verified_at',

                'users.first_name',
                'users.last_name',
                'users.phone as user_phone'
            )
            ->where('afa_orders.status', 'processing')
            ->where('afa_payments.status', 'successful')
            ->orderByDesc('afa_orders.created_at')
            ->get();

        $totalOrders = $orders->count();

        $totalAmount = $orders->sum(function ($order) {
            return (float) ($order->amount ?? 0);
        });

        $successfulPayments = $orders->count();
        $failedPayments = 0;

        return view('admin.afa-orders.processing', compact(
            'orders',
            'totalOrders',
            'totalAmount',
            'successfulPayments',
            'failedPayments'
        ));
    }

    public function markAsInProgress(Request $request): JsonResponse
    {
        $request->validate([
            'action_type' => ['required', 'string', 'in:copy,export'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'string', 'exists:afa_orders,order_id'],
        ]);

        $now = now();

        try {
            $result = DB::transaction(function () use ($request, $now) {
                $orderIds = array_values(array_unique($request->order_ids));

                $eligibleOrderIds = DB::table('afa_orders')
                    ->join('afa_payments', 'afa_orders.order_id', '=', 'afa_payments.order_id')
                    ->whereIn('afa_orders.order_id', $orderIds)
                    ->where('afa_orders.status', 'processing')
                    ->where('afa_payments.status', 'successful')
                    ->lockForUpdate()
                    ->pluck('afa_orders.order_id')
                    ->toArray();

                if (empty($eligibleOrderIds)) {
                    return [
                        'updated' => 0,
                        'skipped' => count($orderIds),
                    ];
                }

                $updated = DB::table('afa_orders')
                    ->whereIn('order_id', $eligibleOrderIds)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'in-progress',
                        'processing_at' => $now,
                        'updated_at' => $now,
                    ]);

                return [
                    'updated' => $updated,
                    'skipped' => count($orderIds) - $updated,
                ];
            });

            return response()->json([
                'success' => $result['updated'] > 0,
                'message' => $this->successMessage(
                    $request->action_type,
                    $result['updated'],
                    $result['skipped']
                ),
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'in_progress_at' => $now->format('d M Y, h:i A'),
            ], $result['updated'] > 0 ? 200 : 409);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update AFA orders. Please try again.',
            ], 500);
        }
    }

    private function successMessage(
        string $actionType,
        int $updated,
        int $skipped
    ): string {
        $action = $actionType === 'export' ? 'exported' : 'copied';

        if ($updated > 0 && $skipped > 0) {
            return "{$updated} successful AFA order(s) {$action} and moved to In Progress. {$skipped} already in progress or unpaid.";
        }

        if ($updated > 0) {
            return "{$updated} successful AFA order(s) {$action} and moved to In Progress successfully.";
        }

        return 'No successful processing AFA order found. These orders are already in progress, unpaid, or no longer available.';
    }
}