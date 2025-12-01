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
}
