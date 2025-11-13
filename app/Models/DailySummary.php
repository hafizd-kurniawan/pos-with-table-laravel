<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class DailySummary extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'tenant_id',
        'date',
        'total_orders',
        'total_items',
        'total_customers',
        'gross_sales',
        'total_discount',
        'subtotal',
        'total_tax',
        'total_service',
        'net_sales',
        'cash_amount',
        'cash_count',
        'qris_amount',
        'qris_count',
        'gopay_amount',
        'gopay_count',
        'is_closed',
        'closed_at',
    ];
    
    protected $casts = [
        'date' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
        'gross_sales' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_service' => 'decimal:2',
        'net_sales' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'qris_amount' => 'decimal:2',
        'gopay_amount' => 'decimal:2',
    ];
    
    /**
     * Relationship to tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
