<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class WaiterController extends Controller
{
    /**
     * Get all order items that are ready to be served.
     */
    /**
     * Get all order items that are ready to be served (Waiting to be taken).
     */
    public function readyToServe(Request $request)
    {
        $items = OrderItem::readyToServe()
            ->with(['product', 'order', 'addons', 'order.table'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get items taken by the current waiter (Serving list).
     */
    public function myTasks(Request $request)
    {
        $user = $request->user();
        if (!$user) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = OrderItem::where('status', OrderItem::STATUS_SERVING)
            ->where('served_by_id', $user->id)
            ->with(['product', 'order', 'addons', 'order.table'])
            ->orderBy('updated_at', 'asc') // Oldest first
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Take an item clearly (Status Ready -> Serving).
     */
    public function takeItem(Request $request, $id)
    {
        $user = $request->user();
        $item = OrderItem::where('id', $id)->firstOrFail();

        // Must be ready
        if ($item->status !== OrderItem::STATUS_READY) {
            return response()->json([
                'success' => false,
                'message' => 'Item is not ready to be taken (Status: ' . $item->status . ')',
            ], 400);
        }

        $item->update([
            'status' => OrderItem::STATUS_SERVING,
            'served_by_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item taken successfully.',
            'data' => $item,
        ]);
    }

    /**
     * Mark an order item as served (completed).
     */
    public function serveItem(Request $request, $id)
    {
        $item = OrderItem::where('id', $id)->firstOrFail();

        // Allow serving if 'ready' (Direct serve) OR 'serving' (Two-step)
        if (!in_array($item->status, [OrderItem::STATUS_READY, OrderItem::STATUS_SERVING])) {
            return response()->json([
                'success' => false,
                'message' => 'Item is not ready or being served.',
            ], 400);
        }

        $item->update(['status' => OrderItem::STATUS_SERVED]);

        // Sync Parent Order Status
        $this->syncOrderStatus($item->order_id);

        return response()->json([
            'success' => true,
            'message' => 'Order served successfully.',
            'data' => $item,
        ]);
    }

    /**
     * Helper to sync Order status based on its Items.
     */
    private function syncOrderStatus($orderId)
    {
        $order = \App\Models\Order::find($orderId);
        if (!$order) return;

        $items = $order->orderItems;
        
        // Exclude canceled items from the total count of "items that need serving"
        $validItems = $items->where('status', '!=', 'canceled');
        $totalValidItems = $validItems->count();
        
        if ($totalValidItems === 0) {
            // If all items are canceled, maybe mark order as canceled? 
            // Or if it was just empty. For now, if all canceled, assume complete or canceled.
            // Let's check status. If order status is pending, maybe cancel. 
            // For now, let's just return to avoid errors.
            return;
        }

        $processingCount = $validItems->whereIn('status', ['cooking', 'processing'])->count();
        $servingCount = $validItems->where('status', 'serving')->count();
        $readyCount = $validItems->where('status', 'ready')->count();
        $pendingCount = $validItems->where('status', 'pending')->count();
        $servedCount = $validItems->whereIn('status', ['served', 'completed'])->count();

        // 1. If ALL valid items are Served -> Order Completed
        if ($servedCount === $totalValidItems) {
            $order->update(['status' => 'complete', 'completed_at' => now()]);
            return;
        }

        // 2. If ANY item is active (Pending, Cooking, Ready, Serving) -> Order Cooking/Active
        if ($processingCount > 0 || $readyCount > 0 || $servingCount > 0 || $pendingCount > 0) {
            // Only update to cooking if it's currently 'pending' or 'ready' (don't revert if already cooking?)
            // Actually, 'cooking' is the general 'active' state for the order in this system.
            if ($order->status !== 'cooking') {
                $order->update(['status' => 'cooking']);
            }
            return;
        }
    }
}
