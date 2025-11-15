<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        
        return view('tenantadmin.settings', compact('tenant'));
    }
    
    /**
     * Update Midtrans Configuration
     */
    public function updateMidtrans(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|string',
            'server_key' => 'required|string',
            'client_key' => 'required|string',
            'is_production' => 'required|boolean',
        ]);
        
        $tenant = Auth::user()->tenant;
        
        $tenant->update([
            'midtrans_merchant_id' => $validated['merchant_id'],
            'midtrans_server_key' => Crypt::encryptString($validated['server_key']),
            'midtrans_client_key' => Crypt::encryptString($validated['client_key']),
            'midtrans_is_production' => $validated['is_production'],
        ]);
        
        return back()->with('success', 'Midtrans configuration updated successfully!');
    }
    
    /**
     * Update N8N Webhook Configuration
     */
    public function updateN8n(Request $request)
    {
        $validated = $request->validate([
            'webhook_url' => 'required|url',
        ]);
        
        $tenant = Auth::user()->tenant;
        
        $tenant->update([
            'n8n_webhook_url' => Crypt::encryptString($validated['webhook_url']),
        ]);
        
        return back()->with('success', 'N8N Webhook URL updated successfully!');
    }
    
    /**
     * Update Firebase Configuration
     */
    public function updateFirebase(Request $request)
    {
        $validated = $request->validate([
            'credentials' => 'required|string',
        ]);
        
        $tenant = Auth::user()->tenant;
        
        // Validate JSON format
        $credentials = json_decode($validated['credentials'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('error', 'Invalid JSON format for Firebase credentials.');
        }
        
        $tenant->update([
            'firebase_credentials' => Crypt::encryptString($validated['credentials']),
        ]);
        
        return back()->with('success', 'Firebase credentials updated successfully!');
    }
    
    /**
     * Delete Configuration
     */
    public function deleteConfig(Request $request, $type)
    {
        $tenant = Auth::user()->tenant;
        
        switch ($type) {
            case 'midtrans':
                $tenant->update([
                    'midtrans_merchant_id' => null,
                    'midtrans_server_key' => null,
                    'midtrans_client_key' => null,
                    'midtrans_is_production' => false,
                ]);
                break;
                
            case 'n8n':
                $tenant->update(['n8n_webhook_url' => null]);
                break;
                
            case 'firebase':
                $tenant->update(['firebase_credentials' => null]);
                break;
                
            default:
                return back()->with('error', 'Invalid configuration type.');
        }
        
        return back()->with('success', ucfirst($type) . ' configuration deleted successfully!');
    }
}
