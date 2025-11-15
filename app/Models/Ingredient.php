<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'unit',
        'current_stock',
        'min_stock',
        'max_stock',
        'cost_per_unit',
        'supplier_id',
        'category',
        'image',
        'description',
        'status',
    ];

    protected $casts = [
        'current_stock' => 'float',
        'min_stock' => 'float',
        'max_stock' => 'float',
        'cost_per_unit' => 'integer',
        'status' => 'string',
    ];

    protected $appends = [
        'stock_value',
        'is_low_stock',
        'stock_status',
    ];

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function ingredientCategory(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'category_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= min_stock');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Auto-generate SKU based on category
     */
    public static function generateSKU($tenantId, $categoryId = null)
    {
        // Get category prefix if provided
        $prefix = 'ING';
        if ($categoryId) {
            $category = IngredientCategory::find($categoryId);
            if ($category && $category->sku_prefix) {
                $prefix = $category->sku_prefix;
            }
        }
        
        // Find last SKU with this prefix
        $lastIngredient = self::where('tenant_id', $tenantId)
            ->where('sku', 'like', $prefix . '-%')
            ->latest('id')
            ->first();

        if ($lastIngredient) {
            // Extract number from last SKU
            $parts = explode('-', $lastIngredient->sku);
            $number = ((int) end($parts)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Accessors
     */
    public function getStockValueAttribute(): float
    {
        return (float) ($this->current_stock * $this->cost_per_unit);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->current_stock <= $this->min_stock) {
            return 'critical';
        } elseif ($this->current_stock <= ($this->min_stock * 1.5)) {
            return 'low';
        } elseif ($this->max_stock && $this->current_stock >= $this->max_stock) {
            return 'overstocked';
        } else {
            return 'safe';
        }
    }

    public function getStockStatusColorAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'danger',
            'critical' => 'danger',
            'low' => 'warning',
            'overstocked' => 'info',
            'safe' => 'success',
            default => 'secondary',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Business Methods
     */
    public function isLowStock(): bool
    {
        return $this->is_low_stock;
    }

    public function canFulfillOrder(float $quantity): bool
    {
        return $this->current_stock >= $quantity;
    }

    public function updateStock(float $newStock): void
    {
        $this->update(['current_stock' => $newStock]);
    }

    public function incrementStock(float $quantity): void
    {
        $this->increment('current_stock', $quantity);
    }

    public function decrementStock(float $quantity): void
    {
        $this->decrement('current_stock', $quantity);
    }
}
