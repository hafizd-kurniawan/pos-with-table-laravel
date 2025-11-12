<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $fillable = [
        'table_id',
        'code',
        'status',
        'total_amount',
        'subtotal',
        'discount_id',
        'discount_amount',
        'tax_amount',
        'tax_percentage',
        'service_charge_amount',
        'service_charge_percentage',
        'placed_at',
        'payment_method',
        'notes',
        'customer_name',
        'customer_phone',
        'customer_email',
        'expired_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'completed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price', 'total', 'notes')
            ->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function isExpired()
    {
        return $this->status === 'expired' || 
               ($this->status === 'pending' && $this->created_at->diffInMinutes(now()) > 2);
    }

    public function canExpire()
    {
        return in_array($this->status, ['pending']);
    }
}
