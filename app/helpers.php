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

if (!function_exists('get_selected_discounts')) {
    /**
     * Get selected discounts from settings (multiple)
     */
    function get_selected_discounts()
    {
        $selectedIds = json_decode(\App\Models\Setting::get('selected_discount_ids', '[]'), true) ?? [];
        if (!empty($selectedIds)) {
            return \App\Models\Discount::where('status', 'active')
                ->where(function($query) {
                    $query->whereNull('expired_date')
                          ->orWhere('expired_date', '>', now());
                })
                ->whereIn('id', $selectedIds)
                ->orderBy('name')
                ->get();
        }
        return collect();
    }
}

if (!function_exists('is_discount_enabled')) {
    /**
     * Check if discount is enabled for orders
     */
    function is_discount_enabled()
    {
        return get_selected_discounts()->isNotEmpty();
    }
}

if (!function_exists('get_selected_taxes')) {
    /**
     * Get selected taxes from settings (multiple)
     */
    function get_selected_taxes()
    {
        $selectedIds = json_decode(\App\Models\Setting::get('selected_tax_ids', '[]'), true) ?? [];
        if (!empty($selectedIds)) {
            return \App\Models\Tax::where('status', 'active')
                ->where('type', 'pajak')
                ->whereIn('id', $selectedIds)
                ->orderBy('name')
                ->get();
        }
        return collect();
    }
}

if (!function_exists('is_tax_enabled')) {
    /**
     * Check if tax is enabled for orders
     */
    function is_tax_enabled()
    {
        return get_selected_taxes()->isNotEmpty();
    }
}

if (!function_exists('tax_percentage')) {
    /**
     * Get total tax percentage from all selected taxes
     */
    function tax_percentage()
    {
        $taxes = get_selected_taxes();
        return $taxes->sum('value');
    }
}

if (!function_exists('get_selected_services')) {
    /**
     * Get selected service charges from settings (multiple)
     */
    function get_selected_services()
    {
        $selectedIds = json_decode(\App\Models\Setting::get('selected_service_ids', '[]'), true) ?? [];
        if (!empty($selectedIds)) {
            return \App\Models\Tax::where('status', 'active')
                ->where('type', 'layanan')
                ->whereIn('id', $selectedIds)
                ->orderBy('name')
                ->get();
        }
        return collect();
    }
}

if (!function_exists('is_service_charge_enabled')) {
    /**
     * Check if service charge is enabled for orders
     */
    function is_service_charge_enabled()
    {
        return get_selected_services()->isNotEmpty();
    }
}

if (!function_exists('get_active_service_charge')) {
    /**
     * Get total service charge percentage from all selected services
     */
    function get_active_service_charge()
    {
        $services = get_selected_services();
        return $services->sum('value');
    }
}
