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
        
        // TODO: Send WhatsApp notification (future enhancement)
        // TODO: Send email to admin (future enhancement)
        // TODO: Push notification to mobile app (future enhancement)
        
        // For now, just log it
        // You can add notification logic here later
    }
}
