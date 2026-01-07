<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ingredient_id',
        'quantity_needed',
        'notes',
    ];

    protected $casts = [
        'quantity_needed' => 'float',
    ];

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Calculate cost for this recipe item
     */
    public function getCostAttribute(): float
    {
        return (float) ($this->quantity_needed * $this->ingredient->cost_per_unit);
    }

    /**
     * Calculate COGS for a product
     */
    public static function calculateProductCOGS(int $productId): float
    {
        return (float) self::where('product_id', $productId)
            ->get()
            ->sum(function ($recipe) {
                return $recipe->quantity_needed * $recipe->ingredient->cost_per_unit;
            });
    }

    /**
     * Check if ingredients are available for production
     */
    public static function canProduceProduct(int $productId, int $quantity = 1): array
    {
        $recipes = self::where('product_id', $productId)
            ->with('ingredient')
            ->get();

        $canProduce = true;
        $insufficientIngredients = [];

        foreach ($recipes as $recipe) {
            $neededQty = $recipe->quantity_needed * $quantity;
            $availableQty = $recipe->ingredient->current_stock;

            if ($availableQty < $neededQty) {
                $canProduce = false;
                $insufficientIngredients[] = [
                    'ingredient' => $recipe->ingredient->name,
                    'needed' => $neededQty,
                    'available' => $availableQty,
                    'shortage' => $neededQty - $availableQty,
                ];
            }
        }

        return [
            'can_produce' => $canProduce,
            'insufficient_ingredients' => $insufficientIngredients,
        ];
    }

    /**
     * Get max quantity that can be produced with current stock
     */
    public static function getMaxProducibleQuantity(int $productId): int
    {
        $recipes = self::where('product_id', $productId)
            ->with('ingredient')
            ->get();

        if ($recipes->isEmpty()) {
            return PHP_INT_MAX; // No recipes = unlimited production
        }

        $maxQuantities = $recipes->map(function ($recipe) {
            if ($recipe->quantity_needed == 0) {
                return PHP_INT_MAX;
            }
            return floor($recipe->ingredient->current_stock / $recipe->quantity_needed);
        });

        return (int) $maxQuantities->min();
    }
}
