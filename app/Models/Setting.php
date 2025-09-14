<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'group', 'label', 'description', 'options'
    ];

    protected $casts = [
        'options' => 'array'
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
}
