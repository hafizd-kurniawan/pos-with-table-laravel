<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notification preferences
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences 
            ?? NotificationPreference::createDefault($user);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update user notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'fcm_enabled' => 'sometimes|boolean',
            'sound_enabled' => 'sometimes|boolean',
            'new_order_alerts' => 'sometimes|boolean',
            'low_stock_alerts' => 'sometimes|boolean',
            'payment_alerts' => 'sometimes|boolean',
            'system_alerts' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $preferences = $user->notificationPreferences 
            ?? NotificationPreference::createDefault($user);

        $preferences->update($request->only([
            'fcm_enabled',
            'sound_enabled',
            'new_order_alerts',
            'low_stock_alerts',
            'payment_alerts',
            'system_alerts',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated',
            'data' => $preferences,
        ]);
    }

    /**
     * Get user notifications
     */
    public function getNotifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $count = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
        ]);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(Request $request)
    {
        $count = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    /**
     * Delete notification
     */
    public function delete(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $user = $request->user();

        $result = $this->notificationService->sendTestNotification($user);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Test notification sent!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test notification',
        ], 500);
    }
}
