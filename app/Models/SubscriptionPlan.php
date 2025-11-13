<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SubscriptionPlan Model
 * 
 * Created: 2025-11-13 04:26:00 WIB
 * Purpose: Subscription plans for multi-tenant SAAS (Bronze/Silver/Gold/Platinum)
 */
class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'duration_days',
        'price',
        'discount_percentage',
        'max_products',
        'max_orders_per_day',
        'max_users',
        'max_tables',
        'max_reservations_per_day',
        'features',
        'is_active',
        'is_popular',
        'display_order',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];
    
    // === HELPER METHODS === //
    
    /**
     * Get final price after discount
     */
    public function getFinalPriceAttribute(): float
    {
        if ($this->discount_percentage > 0) {
            $discount = $this->price * ($this->discount_percentage / 100);
            return $this->price - $discount;
        }
        return $this->price;
    }
    
    /**
     * Get savings amount
     */
    public function getSavingsAttribute(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->price * ($this->discount_percentage / 100);
        }
        return 0;
    }
    
    /**
     * Get duration in human-readable format
     */
    public function getDurationLabelAttribute(): string
    {
        return match($this->duration_days) {
            30 => '1 Bulan',
            90 => '3 Bulan',
            180 => '6 Bulan',
            365 => '1 Tahun',
            default => $this->duration_days . ' Hari',
        };
    }
    
    /**
     * Check if plan has unlimited products
     */
    public function hasUnlimitedProducts(): bool
    {
        return $this->max_products === -1;
    }
    
    /**
     * Check if plan has unlimited orders
     */
    public function hasUnlimitedOrders(): bool
    {
        return $this->max_orders_per_day === -1;
    }
    
    /**
     * Check if plan has unlimited users
     */
    public function hasUnlimitedUsers(): bool
    {
        return $this->max_users === -1;
    }
    
    /**
     * Get formatted features list
     */
    public function getFeaturesList(): array
    {
        $features = [];
        
        // Products
        if ($this->hasUnlimitedProducts()) {
            $features[] = '✅ Produk Unlimited';
        } else {
            $features[] = "✅ {$this->max_products} Produk";
        }
        
        // Orders
        if ($this->hasUnlimitedOrders()) {
            $features[] = '✅ Order Unlimited';
        } else {
            $features[] = "✅ {$this->max_orders_per_day} Order/Hari";
        }
        
        // Users
        if ($this->hasUnlimitedUsers()) {
            $features[] = '✅ Staff Unlimited';
        } else {
            $features[] = "✅ {$this->max_users} Staff";
        }
        
        // Tables
        if ($this->max_tables === -1) {
            $features[] = '✅ Meja Unlimited';
        } else {
            $features[] = "✅ {$this->max_tables} Meja";
        }
        
        // Additional features from JSON
        if (!empty($this->features)) {
            foreach ($this->features as $feature) {
                $features[] = "✅ {$feature}";
            }
        }
        
        return $features;
    }
    
    /**
     * Scope: Only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope: Order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('price');
    }
}
