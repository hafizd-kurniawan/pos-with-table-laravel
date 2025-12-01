<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendLowStockNotification
{
    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $ingredient = $event->ingredient;
        
        // Log the alert
        Log::warning("Low stock alert: {$ingredient->name}", [
            'ingredient_id' => $ingredient->id,
            'current_stock' => $ingredient->current_stock,
            'min_stock' => $ingredient->min_stock,
            'unit' => $ingredient->unit,
            'tenant_id' => $ingredient->tenant_id,
        ]);
        
        // Send FCM notifications to admin users IN THIS TENANT ONLY
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            
            // Get admin users in THIS tenant only
            $adminUsers = \App\Models\User::where('tenant_id', $ingredient->tenant_id)
                ->whereHas('role', function($query) {
                    $query->where('slug', 'admin');
                })
                ->whereNotNull('fcm_token')
                ->get();
            
            $successCount = 0;
            $failedCount = 0;
            
            foreach ($adminUsers as $user) {
                if ($notificationService->sendLowStockNotification($user, $ingredient)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
            
            Log::info("Low stock notifications sent", [
                'tenant_id' => $ingredient->tenant_id,
                'admins_count' => $adminUsers->count(),
                'success' => $successCount,
                'failed' => $failedCount,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send low stock notifications: " . $e->getMessage());
        }
    }
}
