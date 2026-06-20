<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InProgressOrderController extends Controller
{
    public function index()
    {
        $this->autoCompleteDueOrders();

        $autoSetting = $this->getAutoCompleteSetting();

        $timerMinutes = $autoSetting ? (int) $autoSetting->timer_minutes : 0;
        $autoCompleteEnabled = $autoSetting && (int) $autoSetting->is_enabled === 1 && $timerMinutes > 0;

        $orders = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.user_id')
            ->select(
                'orders.*',
                'users.first_name',
                'users.last_name',
                'users.phone as user_phone'
            )
            ->where('orders.status', 'in_progress')
            ->orderByDesc('orders.in_progress_at')
            ->orderByDesc('orders.updated_at')
            ->get();

        $totalOrders = $orders->count();

        $totalAmount = $orders->sum(fn ($order) => (float) ($order->amount ?? 0));

        $totalData = $orders->sum(function ($order) {
            return (float) preg_replace('/[^0-9.]/', '', strtoupper($order->data_size ?? ''));
        });

        $autoCompleteOrders = $orders->whereNotNull('auto_complete_at')->count();
        $manualCompleteOrders = $orders->whereNull('auto_complete_at')->count();

        $nextAutoCompleteAt = $this->nextAutoCompleteAt();

        return view('admin.orders.in-progress', compact(
            'orders',
            'totalOrders',
            'totalAmount',
            'totalData',
            'autoCompleteOrders',
            'manualCompleteOrders',
            'timerMinutes',
            'autoCompleteEnabled',
            'nextAutoCompleteAt'
        ));
    }

    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['nullable', 'string', 'exists:orders,order_id'],
            'complete_all_manual' => ['nullable', 'boolean'],
        ]);

        $now = now();

        try {
            if ($request->boolean('complete_all_manual')) {
                $orderIds = DB::table('orders')
                    ->where('status', 'in_progress')
                    ->whereNull('auto_complete_at')
                    ->pluck('order_id')
                    ->toArray();

                if (empty($orderIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No manual in progress orders found.',
                    ], 404);
                }

                $updated = DB::table('orders')
                    ->whereIn('order_id', $orderIds)
                    ->where('status', 'in_progress')
                    ->whereNull('auto_complete_at')
                    ->update([
                        'status' => 'delivered',
                        'delivered_at' => $now,
                        'completed_at' => $now,
                        'auto_completed' => 0,
                        'updated_at' => $now,
                    ]);

                return response()->json([
                    'success' => true,
                    'updated' => $updated,
                    'order_ids' => $orderIds,
                    'message' => $updated . ' manual order(s) completed successfully.',
                    'completed_at' => $now->format('d M Y, h:i A'),
                ]);
            }

            if (!$request->filled('order_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required.',
                ], 422);
            }

            $updated = DB::transaction(function () use ($request, $now) {
                $order = DB::table('orders')
                    ->where('order_id', $request->order_id)
                    ->where('status', 'in_progress')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return 0;
                }

                return DB::table('orders')
                    ->where('order_id', $order->order_id)
                    ->where('status', 'in_progress')
                    ->update([
                        'status' => 'delivered',
                        'delivered_at' => $now,
                        'completed_at' => $now,
                        'auto_completed' => 0,
                        'updated_at' => $now,
                    ]);
            });

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is no longer in progress or has already been completed.',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'order_ids' => [$request->order_id],
                'message' => 'Order completed manually successfully.',
                'completed_at' => $now->format('d M Y, h:i A'),
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete order. Please try again.',
            ], 500);
        }
    }

    public function checkAutoComplete(): JsonResponse
    {
        $result = $this->autoCompleteDueOrders(true);

        return response()->json([
            'success' => true,
            'updated' => $result['updated'],
            'order_ids' => $result['order_ids'],
            'next_auto_complete_at' => $this->nextAutoCompleteAt(),
        ]);
    }

    public function runAutoComplete(): JsonResponse
    {
        $result = $this->autoCompleteDueOrders(true);

        return response()->json([
            'success' => true,
            'message' => $result['updated'] . ' due order(s) auto completed.',
            'updated' => $result['updated'],
            'order_ids' => $result['order_ids'],
            'ran_at' => now()->format('d M Y, h:i A'),
            'next_auto_complete_at' => $this->nextAutoCompleteAt(),
        ]);
    }

    private function getAutoCompleteSetting()
    {
        return DB::table('auto_complete_settings')
            ->where('setting_key', 'progress_orders_auto_complete')
            ->first();
    }

    private function autoCompleteDueOrders(bool $returnIds = false): array|int
    {
        $setting = $this->getAutoCompleteSetting();

        if (!$setting || (int) $setting->is_enabled !== 1 || (int) $setting->timer_minutes <= 0) {
            return $returnIds
                ? ['updated' => 0, 'order_ids' => []]
                : 0;
        }

        $now = now();

        try {
            return DB::transaction(function () use ($now, $returnIds) {
                $orderIds = DB::table('orders')
                    ->where('status', 'in_progress')
                    ->whereNotNull('auto_complete_at')
                    ->where('auto_complete_at', '<=', $now)
                    ->lockForUpdate()
                    ->pluck('order_id')
                    ->toArray();

                if (empty($orderIds)) {
                    return $returnIds
                        ? ['updated' => 0, 'order_ids' => []]
                        : 0;
                }

                $updated = DB::table('orders')
                    ->whereIn('order_id', $orderIds)
                    ->where('status', 'in_progress')
                    ->update([
                        'status' => 'delivered',
                        'delivered_at' => $now,
                        'completed_at' => $now,
                        'auto_completed' => 1,
                        'updated_at' => $now,
                    ]);

                Log::info('[AUTO COMPLETE ORDERS] Due orders completed', [
                    'updated' => $updated,
                    'order_ids' => $orderIds,
                    'completed_at' => $now->toDateTimeString(),
                ]);

                return $returnIds
                    ? ['updated' => $updated, 'order_ids' => $orderIds]
                    : $updated;
            });
        } catch (\Throwable $e) {
            Log::error('[AUTO COMPLETE ORDERS] Failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            report($e);

            return $returnIds
                ? ['updated' => 0, 'order_ids' => []]
                : 0;
        }
    }

    private function nextAutoCompleteAt()
    {
        return DB::table('orders')
            ->where('status', 'in_progress')
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '>', now())
            ->orderBy('auto_complete_at')
            ->value('auto_complete_at');
    }
}