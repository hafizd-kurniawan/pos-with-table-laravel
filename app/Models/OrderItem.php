<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class OrderItem extends Model
{
    use BelongsToTenant;
    
    //
    protected $fillable = [
        'tenant_id', // CRITICAL: Must be fillable
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'notes',
        'status', // pending, cooking, ready, serving, served, canceled
        'served_by_id' // NEW
    ];
    
    // Status Constants
    const STATUS_HOLD = 'hold'; // NEW: Paid but not sent to kitchen
    const STATUS_PENDING = 'pending';
    const STATUS_COOKING = 'processing'; // Using 'processing' to match DB common practice, or valid enum
    const STATUS_READY = 'ready';
    const STATUS_SERVING = 'serving'; // NEW: Taken by waiter
    const STATUS_SERVED = 'completed'; // Using 'completed' as served
    const STATUS_CANCELED = 'canceled';

    /**
     * Scope for Kitchen Display System (Pending & Cooking)
     */
    public function scopeKitchen($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_COOKING, self::STATUS_READY, self::STATUS_SERVING])
                     ->orderBy('created_at', 'asc');
    }

    /**
     * Scope for Waiter (Ready to Serve)
     */
    public function scopeReadyToServe($query)
    {
        return $query->where('status', self::STATUS_READY)
                     ->orderBy('updated_at', 'asc');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // NEW relationship
    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by_id');
    }

    public function addons()
    {
        return $this->hasMany(OrderItemAddon::class);
    }
}
