<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'ingredient_id',
        'system_qty',
        'physical_qty',
        'difference',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'float',
        'physical_qty' => 'float',
        'difference' => 'float',
    ];

    /**
     * Relationships
     */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Calculate difference
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->difference = $item->physical_qty - $item->system_qty;
        });
    }

    /**
     * Accessors
     */
    public function getHasDifferenceAttribute(): bool
    {
        return $item->difference != 0;
    }

    public function getDifferenceTypeAttribute(): string
    {
        if ($this->difference > 0) {
            return 'overage';
        } elseif ($this->difference < 0) {
            return 'shortage';
        } else {
            return 'match';
        }
    }

    public function getDifferenceColorAttribute(): string
    {
        return match($this->difference_type) {
            'overage' => 'success',
            'shortage' => 'danger',
            'match' => 'secondary',
            default => 'secondary',
        };
    }

    public function getDifferenceValueAttribute(): float
    {
        return (float) (abs($this->difference) * $this->ingredient->cost_per_unit);
    }
}
