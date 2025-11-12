<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
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
