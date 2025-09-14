<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_order_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function draftOrder()
    {
        return $this->belongsTo(DraftOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Methods
    public function calculateTotal()
    {
        return $this->quantity * $this->price;
    }
}
