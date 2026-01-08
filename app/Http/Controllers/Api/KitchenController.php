<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class KitchenController extends Controller
{
    /**
     * Get all active order items for the kitchen (Pending & Cooking).
     */
    public function index(Request $request)
    {
        // Fetch items using the 'kitchen' scope
        // Eager load product and order for details
        $items = OrderItem::kitchen()
            ->with(['product', 'order', 'addons', 'order.table'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Update the status of an order item (e.g., Pending -> Cooking -> Ready).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,ready,completed',
        ]);

        $item = OrderItem::where('id', $id)->firstOrFail();
        
        // Update status
        $item->update(['status' => $request->status]);

        // NEW: Sync Parent Order Status
        $this->syncOrderStatus($item->order_id);

        return response()->json([
            'success' => true,
            'message' => 'Order item status updated successfully.',
            'data' => $item,
        ]);
    }

    // Method for send notification (Copied from OrderController)
    public function sendNotification($title, $message, $tenantId = null)
    {
        Log::info("🔔 Kitchen: sendNotification called", ['title' => $title, 'tenant_id' => $tenantId]);

        // Find user is login (Ideally find Waiter/Cashier)
        // For now, we notify ANY logged in user for this tenant
        $query = User::query();
        if ($tenantId) {
            $query->forTenant($tenantId);
        }
        $user = $query->where('is_login', true)->first(); // Simple strategy: Notify first active user
        // Ideally we should notify ALL waiters

        if ($user && $user->fcm_token) {
            $messaging = app('firebase.messaging');
            $notification = FirebaseNotification::create($title, $message);
            $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification);

            try {
                $messaging->send($cloudMessage);
                Log::info("✅ Kitchen Notification sent successfully");
            } catch (\Exception $e) {
                Log::error('❌ Failed to send Kitchen notification', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Helper to sync Order status based on its Items.
     */
    private function syncOrderStatus($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $items = $order->orderItems;
        
        // Exclude canceled items
        $validItems = $items->where('status', '!=', 'canceled');
        $totalValidItems = $validItems->count();
        
        if ($totalValidItems === 0) return;

        $processingCount = $validItems->whereIn('status', ['cooking', 'processing'])->count();
        $readyCount = $validItems->where('status', 'ready')->count();
        $servingCount = $validItems->where('status', 'serving')->count(); // NEW
        $servedCount = $validItems->whereIn('status', ['served', 'completed'])->count();
        
        // 1. If ALL valid items are Served -> Order Complete
        if ($servedCount === $totalValidItems) {
             if ($order->status !== 'complete') {
                $order->update(['status' => 'complete', 'completed_at' => now()]);
            }
            return;
        }

        // 2. If ANY item is Cooking, Ready, or Serving -> Order Cooking (Active)
        if ($processingCount > 0 || $readyCount > 0 || $servingCount > 0) {
             if ($order->status !== 'cooking') {
                $order->update(['status' => 'cooking']);
            }
            return;
        }
    }
}
