<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'fcm_enabled',
        'sound_enabled',
        'new_order_alerts',
        'low_stock_alerts',
        'payment_alerts',
        'system_alerts',
    ];

    protected $casts = [
        'fcm_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
        'new_order_alerts' => 'boolean',
        'low_stock_alerts' => 'boolean',
        'payment_alerts' => 'boolean',
        'system_alerts' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function createDefault(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function isTypeEnabled(string $type): bool
    {
        if (!$this->fcm_enabled) {
            return false;
        }

        return match($type) {
            'new_order' => $this->new_order_alerts,
            'low_stock' => $this->low_stock_alerts,
            'payment' => $this->payment_alerts,
            'system' => $this->system_alerts,
            default => false,
        };
    }
}
