<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class DraftOrder extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'table_id',
        'total_item',
        'subtotal',
        'tax',
        'discount',
        'discount_amount',
        'service_charge',
        'total',
        'transaction_time',
        'table_number',
        'draft_name'
    ];

    protected $casts = [
        'total_item' => 'integer',
        'subtotal' => 'integer',
        'tax' => 'integer',
        'discount' => 'integer',
        'discount_amount' => 'integer',
        'service_charge' => 'integer',
        'total' => 'integer',
        'table_number' => 'integer',
        'transaction_time' => 'datetime',
    ];

    // Relationships
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function items()
    {
        return $this->hasMany(DraftOrderItem::class);
    }

    // Methods
    public function calculateTotal()
    {
        return $this->subtotal + $this->tax + $this->service_charge - $this->discount_amount;
    }

    public function convertToOrder()
    {
        // Logic to convert draft order to real order
        // Will be implemented in the service layer
    }
}
