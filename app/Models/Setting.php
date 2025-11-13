<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\BelongsToTenant;

class Setting extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'key', 'value', 'type', 'group', 'label', 'description', 'options',
        'selected_discount_ids', 'selected_tax_ids', 'selected_service_ids'
    ];

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
     * Get value attribute - keep as string, don't decode JSON
     * Only decode for display purposes, not for forms
     */
    public function getValueAttribute($value)
    {
        // Don't process if already an array (Filament form state)
        if (is_array($value)) {
            return $value;
        }
        
        // Ensure value is never null for form fields
        if (is_null($value)) {
            return '';
        }
        
        // For color type, always return string
        if (isset($this->attributes['type']) && $this->attributes['type'] === 'color') {
            return (string) $value;
        }
        
        // For file upload, always return string
        if (isset($this->attributes['type']) && $this->attributes['type'] === 'file') {
            return (string) $value;
        }
        
        // For text-based types, ensure string
        if (isset($this->attributes['type']) && in_array($this->attributes['type'], ['text', 'textarea', 'email', 'url', 'number'])) {
            return (string) $value;
        }
        
        // Check if it's a JSON string and if type requires array
        if (isset($this->attributes['type']) && $this->attributes['type'] === 'select' && is_string($value) && $this->isJson($value)) {
            return json_decode($value, true);
        }
        
        return $value;
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
            ['value' => $value]
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
