<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;
    
    //
    protected $fillable = [
        'name', 
        'barcode', 
        'description', 
        'price', 
        'is_featured', 
        'is_favorite', 
        'category_id', 
        'image', 
        'stock', 
        'status',
        'printer_type'
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'is_featured' => 'boolean',
        'is_favorite' => 'boolean',
    ];

    // Scope untuk produk yang available
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('stock', '>', 0);
    }

    // Scope untuk produk yang unavailable atau stock habis
    public function scopeUnavailable($query)
    {
        return $query->where('status', 'unavailable')->orWhere('stock', '<=', 0);
    }

    // Method untuk cek apakah produk tersedia
    public function isAvailable()
    {
        return $this->status === 'available' && $this->stock > 0;
    }

    // Method untuk mengurangi stock
    public function decreaseStock($quantity)
    {
        Log::info('Product decreaseStock called', [
            'product_id' => $this->id,
            'product_name' => $this->name,
            'current_stock' => $this->stock,
            'quantity_to_decrease' => $quantity,
        ]);
        
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            
            Log::info('Stock decremented', [
                'product_id' => $this->id,
                'new_stock' => $this->fresh()->stock,
            ]);
            
            // Auto set to unavailable jika stock habis
            if ($this->fresh()->stock <= 0) {
                $this->update(['status' => 'unavailable']);
                Log::info('Product status set to unavailable due to zero stock', [
                    'product_id' => $this->id,
                ]);
            }
            
            return true;
        }
        
        Log::warning('Insufficient stock for decrease', [
            'product_id' => $this->id,
            'current_stock' => $this->stock,
            'requested_quantity' => $quantity,
        ]);
        
        return false;
    }

    // Method untuk menambah stock
    public function increaseStock($quantity)
    {
        $this->increment('stock', $quantity);
        
        // Auto set to available jika ada stock
        if ($this->fresh()->stock > 0 && $this->status === 'unavailable') {
            $this->update(['status' => 'available']);
        }
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot('quantity', 'price', 'total', 'notes')
            ->withTimestamps();
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        // This will work for both Filament and Flutter
        return config('app.url') . '/storage/' . $this->image;
    }

    // Relationship dengan Recipe (Inventory)
    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * Calculate COGS based on recipes
     */
    public function calculateCOGS(): float
    {
        return \App\Models\Recipe::calculateProductCOGS($this->id);
    }

    /**
     * Check if can be produced with current stock
     */
    public function canBeProduced(int $quantity = 1): array
    {
        return \App\Models\Recipe::canProduceProduct($this->id, $quantity);
    }

    /**
     * Get maximum quantity that can be produced
     */
    public function getMaxProducibleQuantity(): int
    {
        return \App\Models\Recipe::getMaxProducibleQuantity($this->id);
    }
}
