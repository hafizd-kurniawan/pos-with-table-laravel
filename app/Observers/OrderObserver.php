<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        try {
            // Get all kasir and admin users in THIS TENANT ONLY
            $users = User::where('tenant_id', $order->tenant_id)
                ->whereNotNull('fcm_token')
                ->get();

            Log::info("Sending new order notification", [
                'order_id' => $order->id,
                'tenant_id' => $order->tenant_id,
                'users_count' => $users->count(),
            ]);

            $successCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                if ($this->notificationService->sendNewOrderNotification($user, $order)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }

            Log::info("Order notification results", [
                'order_id' => $order->id,
                'success' => $successCount,
                'failed' => $failedCount,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send order notifications: " . $e->getMessage(), [
                'order_id' => $order->id,
                'tenant_id' => $order->tenant_id,
            ]);
        }
    }
    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        try {
            Log::info("Order deleted, attempting to restore stock", ['order_id' => $order->id]);

            // 1. Restore Direct Stock Products
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                if ($product && $product->recipes()->doesntExist()) {
                    $product->increment('stock', $orderItem->quantity);
                    Log::info("Restored direct stock for product", [
                        'product_id' => $product->id,
                        'quantity' => $orderItem->quantity
                    ]);
                }
            }

            // 2. Restore Recipe Ingredients
            // We use InventoryService to handle complex recipe restoration
            $inventoryService = app(\App\Services\InventoryService::class);
            $inventoryService->restoreStockForOrder($order->id);
            
            Log::info("Stock restoration completed for deleted order", ['order_id' => $order->id]);

        } catch (\Exception $e) {
            Log::error("Failed to restore stock for deleted order: " . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
