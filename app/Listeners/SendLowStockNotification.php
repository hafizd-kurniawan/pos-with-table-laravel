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
        
        // Get admin users in THIS tenant only
        $adminUsers = \App\Models\User::where('tenant_id', $ingredient->tenant_id)
            ->whereHas('role', function($query) {
                $query->where('slug', 'admin');
            })
            ->get();
            
        // 1. Send Filament Database Notification (Web Bell Icon)
        foreach ($adminUsers as $user) {
            \Filament\Notifications\Notification::make()
                ->title('⚠️ Low Stock Alert')
                ->body("{$ingredient->name} is running low ({$ingredient->current_stock} {$ingredient->unit})")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url("/admin/ingredients")
                        ->markAsRead(),
                ])
                ->sendToDatabase($user);
                
            // 2. Send Email Notification
            try {
                $user->notify(new \App\Notifications\LowStockEmail($ingredient));
            } catch (\Exception $e) {
                Log::error("Failed to send low stock email to user {$user->id}: " . $e->getMessage());
            }
        }
        
        // 3. Send FCM notifications (Mobile App)
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            
            $fcmUsers = $adminUsers->whereNotNull('fcm_token');
            
            $successCount = 0;
            $failedCount = 0;
            
            foreach ($fcmUsers as $user) {
                if ($notificationService->sendLowStockNotification($user, $ingredient)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
            
            Log::info("Low stock notifications sent", [
                'tenant_id' => $ingredient->tenant_id,
                'admins_count' => $adminUsers->count(),
                'fcm_sent' => $successCount,
                'fcm_failed' => $failedCount,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send FCM low stock notifications: " . $e->getMessage());
        }
    }
}
