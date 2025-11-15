<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class OrderSettingController extends Controller
{
    /**
     * Display order settings page
     */
    public function index()
    {
        $settings = [
            'enable_discount' => Setting::get('enable_discount', '0'),
            'enable_tax' => Setting::get('enable_tax', '1'),
            'enable_service_charge' => Setting::get('enable_service_charge', '1'),
        ];

        return view('order-settings.index', compact('settings'));
    }

    /**
     * Update order settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'enable_discount' => 'required|in:0,1',
            'enable_tax' => 'required|in:0,1',
            'enable_service_charge' => 'required|in:0,1',
        ]);

        // Update settings
        Setting::set('enable_discount', $request->enable_discount);
        Setting::set('enable_tax', $request->enable_tax);
        Setting::set('enable_service_charge', $request->enable_service_charge);

        // Clear cache
        Cache::forget('enable_discount');
        Cache::forget('enable_tax');
        Cache::forget('enable_service_charge');

        return redirect()->route('order-settings.index')
            ->with('success', '✅ Order settings updated successfully!');
    }
}
