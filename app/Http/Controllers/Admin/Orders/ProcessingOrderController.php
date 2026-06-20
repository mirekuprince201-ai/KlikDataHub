<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessingOrderController extends Controller
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
            ->where('orders.status', 'processing')
            ->whereNull('orders.in_progress_at')
            ->orderByDesc('orders.created_at')
            ->get();

        $totalOrders = $orders->count();

        $totalAmount = $orders->sum(function ($order) {
            return (float) ($order->amount ?? 0);
        });

        $successfulPayments = $orders
            ->where('payment_status', 'successful')
            ->count();

        $failedPayments = $orders
            ->where('payment_status', 'failed')
            ->count();

        return view('admin.orders.processing', compact(
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
            'order_ids.*' => ['required', 'string', 'exists:orders,order_id'],
        ]);

        $now = now();

        try {
            $result = DB::transaction(function () use ($request, $now) {
                $orderIds = array_values(array_unique($request->order_ids));

                $autoCompleteSetting = $this->getAutoCompleteSetting();

                $autoCompleteAt = null;
                $autoCompleteMinutes = null;
                $autoCompleteEnabled = false;

                if ($autoCompleteSetting && (int) $autoCompleteSetting->is_enabled === 1) {
                    $minutes = (int) ($autoCompleteSetting->timer_minutes ?? 0);

                    if ($minutes > 0) {
                        $autoCompleteEnabled = true;
                        $autoCompleteMinutes = $minutes;
                        $autoCompleteAt = $now->copy()->addMinutes($minutes);
                    }
                }

                $eligibleOrderIds = DB::table('orders')
                    ->whereIn('order_id', $orderIds)
                    ->where('status', 'processing')
                    ->whereNull('in_progress_at')
                    ->lockForUpdate()
                    ->pluck('order_id')
                    ->toArray();

                if (empty($eligibleOrderIds)) {
                    return [
                        'updated' => 0,
                        'skipped' => count($orderIds),
                        'auto_complete_enabled' => $autoCompleteEnabled,
                        'auto_complete_minutes' => $autoCompleteMinutes,
                        'auto_complete_at' => $autoCompleteAt,
                    ];
                }

                $updated = DB::table('orders')
                    ->whereIn('order_id', $eligibleOrderIds)
                    ->where('status', 'processing')
                    ->whereNull('in_progress_at')
                    ->update([
                        'status' => 'in_progress',
                        'in_progress_at' => $now,
                        'processing_at' => $now,
                        'auto_complete_at' => $autoCompleteAt,
                        'auto_completed' => 0,
                        'updated_at' => $now,
                    ]);

                return [
                    'updated' => $updated,
                    'skipped' => count($orderIds) - $updated,
                    'auto_complete_enabled' => $autoCompleteEnabled,
                    'auto_complete_minutes' => $autoCompleteMinutes,
                    'auto_complete_at' => $autoCompleteAt,
                ];
            });

            return response()->json([
                'success' => $result['updated'] > 0,
                'message' => $this->successMessage(
                    $request->action_type,
                    $result['updated'],
                    $result['skipped'],
                    $result['auto_complete_enabled'],
                    $result['auto_complete_minutes']
                ),
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'in_progress_at' => $now->format('d M Y, h:i A'),
                'auto_complete_enabled' => $result['auto_complete_enabled'],
                'auto_complete_minutes' => $result['auto_complete_minutes'],
                'auto_complete_at' => $result['auto_complete_at']
                    ? $result['auto_complete_at']->format('d M Y, h:i A')
                    : null,
            ], $result['updated'] > 0 ? 200 : 409);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update orders. Please try again.',
            ], 500);
        }
    }

    private function getAutoCompleteSetting(): ?object
    {
        return DB::table('auto_complete_settings')
            ->where('setting_key', 'progress_orders_auto_complete')
            ->whereNotNull('timer_minutes')
            ->first();
    }

    private function successMessage(
        string $actionType,
        int $updated,
        int $skipped,
        bool $autoCompleteEnabled,
        ?int $autoCompleteMinutes
    ): string {
        $action = $actionType === 'export' ? 'exported' : 'copied';

        $autoCompleteText = $autoCompleteEnabled
            ? " Auto complete is active and set for {$autoCompleteMinutes} minute(s)."
            : " Auto complete is not active.";

        if ($updated > 0 && $skipped > 0) {
            return "{$updated} order(s) {$action} and moved to In Progress. {$skipped} already in progress.{$autoCompleteText}";
        }

        if ($updated > 0) {
            return "{$updated} order(s) {$action} and moved to In Progress successfully.{$autoCompleteText}";
        }

        return 'No processing order found. These orders are already in progress or no longer available.';
    }
}