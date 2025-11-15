<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Setting;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;

class PosSettingsController extends Controller
{
    /**
     * Get all POS settings (discounts, taxes, services)
     * Returns only active items for cashier POS use
     * Respects order-settings configuration (enable/disable features)
     * 
     * Unified endpoint untuk efisiensi (1 API call instead of 3)
     */
    public function index(): JsonResponse
    {
        // Get selected IDs from settings
        $selectedDiscountIds = json_decode(Setting::get('selected_discount_ids', '[]'), true) ?? [];
        $selectedTaxIds = json_decode(Setting::get('selected_tax_ids', '[]'), true) ?? [];
        $selectedServiceIds = json_decode(Setting::get('selected_service_ids', '[]'), true) ?? [];

        // Get discounts filtered by selected IDs
        $discounts = !empty($selectedDiscountIds)
            ? Discount::where('status', 'active')
                ->where(function($query) {
                    $query->whereNull('expired_date')
                          ->orWhere('expired_date', '>', now());
                })
                ->whereIn('id', $selectedDiscountIds)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'value', 'description'])
            : collect();

        // Get taxes filtered by selected IDs
        $taxes = !empty($selectedTaxIds)
            ? Tax::where('status', 'active')
                ->where('type', 'pajak')
                ->whereIn('id', $selectedTaxIds)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'value', 'description'])
            : collect();

        // Get services filtered by selected IDs
        $services = !empty($selectedServiceIds)
            ? Tax::where('status', 'active')
                ->where('type', 'layanan')
                ->whereIn('id', $selectedServiceIds)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'value', 'description'])
            : collect();

        return response()->json([
            'success' => true,
            'message' => 'POS Settings retrieved successfully',
            'data' => [
                'discounts' => $discounts,
                'taxes' => $taxes,
                'services' => $services,
                'configuration' => [
                    'enable_discount' => !$discounts->isEmpty(),
                    'enable_tax' => !$taxes->isEmpty(),
                    'enable_service_charge' => !$services->isEmpty(),
                ],
            ]
        ]);
    }
}
