<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * TenantSettingsController
 * 
 * Created: 2025-11-13 05:00:00 WIB
 * Purpose: Manage tenant-specific configurations (Midtrans, N8N, Firebase)
 * 
 * Endpoints:
 * - GET    /api/tenant/settings - Get all tenant settings
 * - POST   /api/tenant/settings/midtrans - Update Midtrans config
 * - POST   /api/tenant/settings/n8n - Update N8N webhook config
 * - POST   /api/tenant/settings/firebase - Update Firebase config
 * - DELETE /api/tenant/settings/midtrans - Delete Midtrans config
 * - DELETE /api/tenant/settings/n8n - Delete N8N webhook config
 * - DELETE /api/tenant/settings/firebase - Delete Firebase config
 * - POST   /api/tenant/settings/midtrans/test - Test Midtrans connection
 */
class TenantSettingsController extends Controller
{
    /**
     * Get current tenant from app instance
     */
    protected function getCurrentTenant()
    {
        return app('tenant');
    }
    
    /**
     * Get all tenant settings
     * 
     * GET /api/tenant/settings
     */
    public function index()
    {
        $tenant = $this->getCurrentTenant();
        
        return response()->json([
            'success' => true,
            'tenant' => [
                'id' => $tenant->id,
                'subdomain' => $tenant->subdomain,
                'business_name' => $tenant->business_name,
            ],
            'settings' => [
                'midtrans' => [
                    'configured' => $tenant->hasMidtransConfigured(),
                    'merchant_id' => $tenant->midtrans_merchant_id ?? null,
                    'is_production' => $tenant->midtrans_is_production ?? false,
                ],
                'n8n' => [
                    'configured' => $tenant->hasN8NConfigured(),
                    'webhook_url' => $tenant->n8n_webhook_url ? '***configured***' : null,
                ],
                'firebase' => [
                    'configured' => $tenant->hasFirebaseConfigured(),
                    'project_id' => $tenant->firebase_project_id ?? null,
                ],
            ],
        ]);
    }
    
    /**
     * Update Midtrans configuration
     * 
     * POST /api/tenant/settings/midtrans
     * Body: {
     *   "merchant_id": "G123456789",
     *   "client_key": "SB-Mid-client-xxx",
     *   "server_key": "SB-Mid-server-xxx",
     *   "is_production": false
     * }
     */
    public function updateMidtrans(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|string|max:255',
            'client_key' => 'required|string|max:255',
            'server_key' => 'required|string|max:255',
            'is_production' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $tenant = $this->getCurrentTenant();
        
        try {
            $tenant->update([
                'midtrans_merchant_id' => $request->merchant_id,
                'midtrans_client_key' => $request->client_key,
                'midtrans_server_key' => $request->server_key,
                'midtrans_is_production' => $request->is_production ?? false,
            ]);
            
            Log::info("Midtrans config updated for tenant: {$tenant->subdomain}");
            
            return response()->json([
                'success' => true,
                'message' => 'Midtrans configuration updated successfully',
                'data' => [
                    'merchant_id' => $tenant->midtrans_merchant_id,
                    'is_production' => $tenant->midtrans_is_production,
                    'configured' => $tenant->hasMidtransConfigured(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to update Midtrans config for tenant {$tenant->subdomain}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Midtrans configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete Midtrans configuration
     * 
     * DELETE /api/tenant/settings/midtrans
     */
    public function deleteMidtrans()
    {
        $tenant = $this->getCurrentTenant();
        
        try {
            $tenant->update([
                'midtrans_merchant_id' => null,
                'midtrans_client_key' => null,
                'midtrans_server_key' => null,
                'midtrans_is_production' => false,
            ]);
            
            Log::info("Midtrans config deleted for tenant: {$tenant->subdomain}");
            
            return response()->json([
                'success' => true,
                'message' => 'Midtrans configuration deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Midtrans configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Test Midtrans connection
     * 
     * POST /api/tenant/settings/midtrans/test
     */
    public function testMidtrans()
    {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant->hasMidtransConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Midtrans is not configured yet',
            ], 400);
        }
        
        // Simple validation - check if keys are set
        return response()->json([
            'success' => true,
            'message' => 'Midtrans configuration looks valid',
            'data' => [
                'merchant_id' => $tenant->midtrans_merchant_id,
                'is_production' => $tenant->midtrans_is_production,
                'environment' => $tenant->midtrans_is_production ? 'Production' : 'Sandbox',
            ],
        ]);
    }
    
    /**
     * Update N8N webhook configuration
     * 
     * POST /api/tenant/settings/n8n
     * Body: {
     *   "webhook_url": "https://n8n.example.com/webhook/xxx"
     * }
     */
    public function updateN8N(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $tenant = $this->getCurrentTenant();
        
        try {
            $tenant->update([
                'n8n_webhook_url' => $request->webhook_url,
            ]);
            
            Log::info("N8N webhook config updated for tenant: {$tenant->subdomain}");
            
            return response()->json([
                'success' => true,
                'message' => 'N8N webhook configuration updated successfully',
                'data' => [
                    'configured' => $tenant->hasN8NConfigured(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to update N8N config for tenant {$tenant->subdomain}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update N8N webhook configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete N8N webhook configuration
     * 
     * DELETE /api/tenant/settings/n8n
     */
    public function deleteN8N()
    {
        $tenant = $this->getCurrentTenant();
        
        try {
            $tenant->update([
                'n8n_webhook_url' => null,
            ]);
            
            Log::info("N8N webhook config deleted for tenant: {$tenant->subdomain}");
            
            return response()->json([
                'success' => true,
                'message' => 'N8N webhook configuration deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete N8N webhook configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update Firebase configuration
     * 
     * POST /api/tenant/settings/firebase
     * Body: {
     *   "project_id": "my-project-id",
     *   "service_account": {...} // JSON content
     * }
     */
    public function updateFirebase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|string|max:255',
            'service_account' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $tenant = $this->getCurrentTenant();
        
        try {
            $tenant->update([
                'firebase_project_id' => $request->project_id,
                'firebase_service_account' => $request->service_account,
            ]);
            
            Log::info("Firebase config updated for tenant: {$tenant->subdomain}");
            
            return response()->json([
                'success' => true,
                'message' => 'Firebase configuration updated successfully',
                'data' => [
                    'project_id' => $tenant->firebase_project_id,
                    'configured' => $tenant->hasFirebaseConfigured(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to update Firebase config for tenant {$tenant->subdomain}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Firebase configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete Firebase configuration
     * 
     * DELETE /api/tenant/settings/firebase
     */
    public function deleteFirebase()
    {
        $tenant = $this->getCurrentTenant();
        
        try {
            $tenant->update([
                'firebase_project_id' => null,
                'firebase_service_account' => null,
            ]);
            
            Log::info("Firebase config deleted for tenant: {$tenant->subdomain}");
            
            return response()->json([
                'success' => true,
                'message' => 'Firebase configuration deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Firebase configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
