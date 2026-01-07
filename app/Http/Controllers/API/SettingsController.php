<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Get all tenant settings
     * Used by Flutter app to get restaurant configuration
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get tenant_id from authenticated user or from request
        $tenantId = $this->getTenantId($request);
        
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        // Get all settings for this tenant
        $settings = Setting::where('tenant_id', $tenantId)->get();
        
        // Transform to key-value pairs for easy access
        $settingsMap = $settings->mapWithKeys(function ($item) {
            return [$item->key => [
                'value' => $item->value,
                'type' => $item->type,
                'label' => $item->label,
            ]];
        });
        
        // Grouped by category for organized display
        $settingsByGroup = $settings->groupBy('group')->map(function ($group) {
            return $group->map(function ($item) {
                return [
                    'key' => $item->key,
                    'value' => $item->value,
                    'type' => $item->type,
                    'label' => $item->label,
                    'description' => $item->description,
                ];
            });
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settingsMap,
                'grouped' => $settingsByGroup,
                'tenant_id' => $tenantId,
            ]
        ]);
    }
    
    /**
     * Get specific setting value by key
     * 
     * @param Request $request
     * @param string $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $key)
    {
        $tenantId = $this->getTenantId($request);
        
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID required'
            ], 400);
        }
        
        $setting = Setting::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
            
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'label' => $setting->label,
                'description' => $setting->description,
            ]
        ]);
    }
    
    /**
     * Get tenant ID from various sources
     * Priority: Auth user > Request header > Request parameter
     */
    private function getTenantId(Request $request)
    {
        // From authenticated user
        if (auth()->check()) {
            $userId = auth()->id();
            $user = \DB::table('users')->where('id', $userId)->first();
            if ($user && $user->tenant_id) {
                return $user->tenant_id;
            }
        }
        
        // From request header (for public API)
        if ($request->header('X-Tenant-ID')) {
            return $request->header('X-Tenant-ID');
        }
        
        // From request parameter
        if ($request->input('tenant_id')) {
            return $request->input('tenant_id');
        }
        
        return null;
    }
}
