<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getValue(Request $request)
    {
        try {
            $key = $request->query('key');
            $keys = $request->query('keys');
            $group = $request->query('group');
            
            // Jika ada parameter 'keys' - ambil multiple settings
            if ($keys) {
                $keysArray = explode(',', $keys);
                $settings = Setting::whereIn('key', $keysArray)->get()->keyBy('key');
                
                $result = [];
                foreach ($keysArray as $keyItem) {
                    $result[$keyItem] = $settings->has($keyItem) ? $settings[$keyItem]->value : null;
                }
                
                return response()->json([
                    'data' => $result,
                ]);
            }
            
            // Jika ada parameter 'group' - ambil berdasarkan group
            if ($group) {
                $settings = Setting::where('group', $group)->get();
                
                $result = [];
                foreach ($settings as $setting) {
                    $result[$setting->key] = $setting->value;
                }
                
                return response()->json([
                    'data' => $result,
                ]);
            }
            
            // Jika ada parameter 'key' - ambil single setting
            if ($key) {
                $setting = Setting::where('key', $key)->first();
                
                if (!$setting) {
                    return response()->json([
                        'message' => 'Setting not found',
                    ], 404);
                }
                
                return response()->json([
                    'value' => $setting->value,
                ]);
            }
            
            // Jika tidak ada parameter - ambil semua settings
            // Use DB facade to avoid potential Model recursion issues
            // But we need tenant_id. Try to get it safely.
            
            try {
                // Try standard way first
                $settings = Setting::all();
            } catch (\Throwable $e) {
                \Log::error('Setting::all() failed: ' . $e->getMessage());
                
                // Fallback to DB query with explicit tenant filtering
                $query = \DB::table('settings');
                
                // Try to get tenant_id from auth
                $tenantId = null;
                if (auth()->check()) {
                    $user = \DB::table('users')->where('id', auth()->id())->first();
                    $tenantId = $user->tenant_id ?? null;
                }
                
                if ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }
                
                $settings = $query->get();
            }
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }
            
            return response()->json([
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            \Log::error('SettingController Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}