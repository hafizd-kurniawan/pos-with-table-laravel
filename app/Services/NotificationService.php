<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-auth.json'));
            
            $this->messaging = $factory->createMessaging();
            
            Log::info('Firebase messaging service initialized successfully');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Send notification to user (with tenant isolation check)
     */
    public function sendToUser(User $user, array $data): bool
    {
        // Check FCM token
        if (!$user->hasFcmToken()) {
            Log::warning("User {$user->id} has no FCM token");
            return false;
        }

        // Check Firebase initialized
        if (!$this->messaging) {
            Log::error('Firebase messaging not initialized');
            return false;
        }

        // Check user preferences (if exists)
        $preferences = $user->notificationPreferences;
        if ($preferences && !$preferences->isTypeEnabled($data['type'])) {
            Log::info("User {$user->id} has disabled {$data['type']} notifications");
            return false;
        }

        // Save to database
        $notification = Notification::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => $data['data'] ?? null,
        ]);

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(
                    FirebaseNotification::create($data['title'], $data['body'])
                )
                ->withData([
                    'type' => $data['type'],
                    'id' => (string) ($data['data']['id'] ?? ''),
                    'notification_id' => (string) $notification->id,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $this->messaging->send($message);
            $notification->update(['is_sent' => true]);

            Log::info("Notification sent to user {$user->id}", [
                'type' => $data['type'],
                'tenant_id' => $user->tenant_id,
                'notification_id' => $notification->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send notification: " . $e->getMessage());
            $notification->update([
                'is_sent' => false,
                'error_message' => $e->getMessage(),
            ]);

            // Clear invalid token
            if (str_contains($e->getMessage(), 'not-found') ||
                str_contains($e->getMessage(), 'invalid-registration-token')) {
                $user->clearFcmToken();
                Log::warning("Cleared invalid FCM token for user {$user->id}");
            }

            return false;
        }
    }

    /**
     * Send new order notification
     */
    public function sendNewOrderNotification(User $user, $order): bool
    {
        return $this->sendToUser($user, [
            'type' => 'new_order',
            'title' => '🛒 New Order!',
            'body' => "Order #{$order->id}" . 
                      ($order->table_number ? " - Table {$order->table_number}" : '') .
                      " - Rp " . number_format($order->total, 0, ',', '.'),
            'data' => [
                'id' => (string) $order->id,
                'order_type' => $order->order_type ?? 'dine-in',
                'table_number' => $order->table_number ?? '',
                'total_amount' => $order->total,
            ],
        ]);
    }

    /**
     * Send low stock notification
     */
    public function sendLowStockNotification(User $user, $ingredient): bool
    {
        return $this->sendToUser($user, [
            'type' => 'low_stock',
            'title' => '📦 Low Stock Alert',
            'body' => "{$ingredient->name} - Stock: {$ingredient->current_stock} (Min: {$ingredient->min_stock})",
            'data' => [
                'id' => (string) $ingredient->id,
                'name' => $ingredient->name,
                'current_stock' => $ingredient->current_stock,
                'min_stock' => $ingredient->min_stock,
            ],
        ]);
    }

    /**
     * Send to tenant users (multi-tenant isolation)
     */
    public function sendToTenantUsers(int $tenantId, array $data): array
    {
        // Get users in this tenant only
        $users = User::where('tenant_id', $tenantId)
            ->whereNotNull('fcm_token')
            ->get();

        $results = ['success' => 0, 'failed' => 0];
        
        foreach ($users as $user) {
            if ($this->sendToUser($user, $data)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info("Sent notifications to tenant {$tenantId}", $results);

        return $results;
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(User $user): bool
    {
        return $this->sendToUser($user, [
            'type' => 'test',
            'title' => '🧪 Test Notification',
            'body' => 'This is a test notification from Laravel backend!',
            'data' => [
                'id' => 'test-' . time(),
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
