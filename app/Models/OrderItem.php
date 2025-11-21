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
        'notes'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
