<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getValue(Request $request)
    {
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
        $settings = Setting::all();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }
        
        return response()->json([
            'data' => $result,
        ]);
    }
}