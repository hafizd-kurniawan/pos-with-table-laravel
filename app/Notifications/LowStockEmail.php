<?php

namespace App\Notifications;

use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $ingredient;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ingredient $ingredient)
    {
        $this->ingredient = $ingredient;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('⚠️ Low Stock Alert: ' . $this->ingredient->name)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('The following item is running low on stock:')
                    ->line('**Item:** ' . $this->ingredient->name)
                    ->line('**Current Stock:** ' . $this->ingredient->current_stock . ' ' . $this->ingredient->unit)
                    ->line('**Minimum Stock:** ' . $this->ingredient->min_stock . ' ' . $this->ingredient->unit)
                    ->action('View Inventory', url('/admin/ingredients'))
                    ->line('Please restock this item soon to avoid running out.')
                    ->priority(1); // High priority
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ingredient_id' => $this->ingredient->id,
            'name' => $this->ingredient->name,
            'current_stock' => $this->ingredient->current_stock,
        ];
    }
}
