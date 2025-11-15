<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'tenant_id',
        'ingredient_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
        'moved_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'stock_before' => 'float',
        'stock_after' => 'float',
        'moved_at' => 'datetime',
        'type' => 'string',
    ];

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the polymorphic reference
     */
    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        $modelMap = [
            'order' => Order::class,
            'purchase_order' => PurchaseOrder::class,
            'stock_opname' => StockOpname::class,
            'manual_adjustment' => null,
            'waste' => null,
        ];

        $modelClass = $modelMap[$this->reference_type] ?? null;

        if ($modelClass) {
            return $modelClass::find($this->reference_id);
        }

        return null;
    }

    /**
     * Scopes
     */
    public function scopeIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    public function scopeAdjustment($query)
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByReferenceType($query, string $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('moved_at', [$startDate, $endDate]);
    }

    /**
     * Accessors
     */
    public function getMovementDescriptionAttribute(): string
    {
        $descriptions = [
            'order' => 'Used in order',
            'purchase_order' => 'Purchase order received',
            'stock_opname' => 'Stock opname adjustment',
            'manual_adjustment' => 'Manual adjustment',
            'waste' => 'Waste/Disposal',
        ];

        return $descriptions[$this->reference_type] ?? 'Stock movement';
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_IN => 'success',
            self::TYPE_OUT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            default => 'Unknown',
        };
    }
}
