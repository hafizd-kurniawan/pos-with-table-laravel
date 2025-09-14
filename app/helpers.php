<?php

if (!function_exists('setting')) {
    /**
     * Get setting value by key
     */
    function setting($key, $default = null)
    {
        try {
            return \App\Models\Setting::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('app_name')) {
    /**
     * Get application name from settings
     */
    function app_name()
    {
        return setting('app_name', config('app.name', 'Laravel POS'));
    }
}

if (!function_exists('tax_percentage')) {
    /**
     * Get application tagline from settings
     */
    function tax_percentage()
    {
        return setting('tax_percentage', '11');
    }
}

if (!function_exists('app_logo')) {
    /**
     * Get application logo URL from settings
     */
    function app_logo()
    {
        $logo = setting('logo_url');
        if ($logo) {
            return asset('storage/' . $logo);
        }
        return asset('images/logo-default.svg');
    }
}

if (!function_exists('primary_color')) {
    /**
     * Get primary color from settings
     */
    function primary_color()
    {
        return setting('primary_color', '#000000');
    }
}
