<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class ProductAddon extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'price',
        'is_available',
        'ingredient_id',
        'quantity_needed',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'quantity_needed' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Sync Cost based on Ingredient
     */
    public function syncCost(): void
    {
        if ($this->ingredient_id && $this->ingredient) {
            $newCost = $this->ingredient->cost_per_unit * $this->quantity_needed;
            $this->update(['cost' => $newCost]);
        }
    }
}
