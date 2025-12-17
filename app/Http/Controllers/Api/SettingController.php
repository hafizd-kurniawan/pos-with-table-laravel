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
            
            // BLACKLIST: Sensitive keys that should NEVER be returned to frontend
            $blacklist = [
                'midtrans_server_key',
                'firebase_fcm_token', 
                'n8n_webhook_url',
                'smtp_password',
                'mail_password'
            ];

            // Helper to filter sensitive keys
            $filterSensitive = function($k) use ($blacklist) {
                return in_array($k, $blacklist);
            };
            
            // Jika ada parameter 'keys' - ambil multiple settings
            if ($keys) {
                $keysArray = explode(',', $keys);
                
                // Filter requested keys against blacklist
                $keysArray = array_filter($keysArray, fn($k) => !$filterSensitive($k));
                
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
                    if ($filterSensitive($setting->key)) continue; // Skip sensitive
                    $result[$setting->key] = $setting->value;
                }
                
                return response()->json([
                    'data' => $result,
                ]);
            }
            
            // Jika ada parameter 'key' - ambil single setting
            if ($key) {
                if ($filterSensitive($key)) {
                    return response()->json(['message' => 'Access denied to this setting'], 403);
                }

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
                if ($filterSensitive($setting->key)) continue; // Skip sensitive
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