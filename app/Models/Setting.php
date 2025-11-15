<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\BelongsToTenant;

class Setting extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'key',  // Only fillable when creating, protected in update
        'value', 
        'type', 
        'group', 
        'label', 
        'description', 
        'options',
        'selected_discount_ids', 
        'selected_tax_ids', 
        'selected_service_ids'
    ];
    
    /**
     * CRITICAL: Key should NEVER change after creation
     */
    protected $guarded = ['id', 'tenant_id'];

    protected $casts = [
        'options' => 'array',
        'selected_discount_ids' => 'array',
        'selected_tax_ids' => 'array',
        'selected_service_ids' => 'array',
    ];
    
    /**
     * Set value attribute - convert array to JSON if needed
     */
    public function setValueAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
    
    /**
     * Get value attribute - SIMPLE & SAFE
     * Just return the value as-is (string)
     */
    public function getValueAttribute($value)
    {
        // CRITICAL: Use $value parameter, NOT $this->attributes['value']!
        // Eloquent already processes $value from database
        
        // Handle null
        if (is_null($value)) {
            return '';
        }
        
        // If already array (shouldn't happen, but safe)
        if (is_array($value)) {
            return json_encode($value);
        }
        
        // Return as string
        return (string) $value;
    }
    
    /**
     * Check if string is JSON
     */
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value by key
     */
    public static function set($key, $value)
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'label' => ucfirst(str_replace('_', ' ', $key)),
                'type' => 'boolean',
                'group' => 'order',
            ]
        );
        
        // Clear specific cache and general cache
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
        
        return $setting;
    }

    /**
     * Boot method to clear cache when model changes
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function () {
            Cache::forget('settings.all');
        });
        
        static::deleted(function () {
            Cache::forget('settings.all');
        });
    }

    /**
     * Get all settings grouped
     */
    public static function getAllGrouped()
    {
        return static::all()->groupBy('group');
    }

    /**
     * Clear cache for all settings
     */
    public static function clearCache()
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
    }

    /**
     * Get selected discounts
     */
    public function getSelectedDiscounts()
    {
        if (!$this->selected_discount_ids) {
            return collect();
        }
        return \App\Models\Discount::active()->whereIn('id', $this->selected_discount_ids)->get();
    }

    /**
     * Get selected taxes
     */
    public function getSelectedTaxes()
    {
        if (!$this->selected_tax_ids) {
            return collect();
        }
        return \App\Models\Tax::active()->whereIn('id', $this->selected_tax_ids)->get();
    }

    /**
     * Get selected services
     */
    public function getSelectedServices()
    {
        if (!$this->selected_service_ids) {
            return collect();
        }
        return \App\Models\Tax::active()->whereIn('id', $this->selected_service_ids)->get();
    }
}
