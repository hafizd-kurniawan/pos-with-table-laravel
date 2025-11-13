<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * IdentifyTenant Middleware
 * 
 * Created: 2025-11-13 04:34:00 WIB
 * Purpose: Identify current tenant from HTTP header (local) or subdomain (production)
 * 
 * Features:
 * - Dual identification: HTTP header (X-Tenant) for local testing + subdomain for production
 * - Auto-check trial/subscription expiry
 * - Suspend blocked tenants
 * - Set current tenant in app instance & config
 */
class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;
        
        // METHOD 1: Check HTTP Header (for local testing)
        // Usage: Add header "X-Tenant: resto-a" in Postman/Flutter
        if ($request->hasHeader('X-Tenant')) {
            $subdomain = $request->header('X-Tenant');
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            
            if ($tenant) {
                Log::info("Tenant identified via header: {$subdomain} (ID: {$tenant->id})");
            }
        }
        
        // METHOD 2: Check Subdomain (for production)
        // Usage: https://resto-a.possaas.com
        if (!$tenant) {
            $host = $request->getHost();
            $subdomain = $this->extractSubdomain($host);
            
            // Skip admin subdomain (for super admin panel)
            if ($subdomain && $subdomain !== 'admin') {
                $tenant = Tenant::where('subdomain', $subdomain)->first();
                
                if ($tenant) {
                    Log::info("Tenant identified via subdomain: {$subdomain} (ID: {$tenant->id})");
                }
            }
        }
        
        // No tenant found
        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'Please provide valid tenant subdomain via header (X-Tenant) or subdomain',
                'examples' => [
                    'header' => 'X-Tenant: resto-a',
                    'subdomain' => 'https://resto-a.possaas.com',
                ],
            ], 404);
        }
        
        // Check tenant status
        $statusCheck = $this->checkTenantStatus($tenant);
        if ($statusCheck !== null) {
            return $statusCheck; // Return error response
        }
        
        // Set current tenant globally
        app()->instance('tenant', $tenant);
        config(['app.current_tenant' => $tenant]);
        session(['tenant_id' => $tenant->id]);
        
        // Add tenant info to request
        $request->merge(['tenant_id' => $tenant->id]);
        
        return $next($request);
    }
    
    /**
     * Extract subdomain from host
     */
    protected function extractSubdomain(string $host): ?string
    {
        // For localhost testing (no subdomain)
        if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
            return null;
        }
        
        // Split by dots
        $parts = explode('.', $host);
        
        // Need at least 3 parts for subdomain (e.g., resto-a.possaas.com)
        if (count($parts) >= 3) {
            return $parts[0]; // Return first part (subdomain)
        }
        
        return null;
    }
    
    /**
     * Check tenant status and return error if not active
     */
    protected function checkTenantStatus(Tenant $tenant): ?Response
    {
        // Check if suspended
        if ($tenant->status === 'suspended') {
            return response()->json([
                'error' => 'Account suspended',
                'message' => 'Your account has been suspended. Please contact support.',
                'support_email' => 'support@possaas.com',
            ], 403);
        }
        
        // Check trial expiry
        if ($tenant->status === 'trial') {
            if ($tenant->trial_ends_at < now()) {
                // Auto-update to expired
                $tenant->update(['status' => 'expired']);
                
                return response()->json([
                    'error' => 'Trial expired',
                    'message' => 'Your trial period has ended. Please subscribe to continue.',
                    'trial_ended_at' => $tenant->trial_ends_at->format('d M Y H:i'),
                    'days_expired' => now()->diffInDays($tenant->trial_ends_at),
                    'available_plans' => $this->getAvailablePlans(),
                ], 403);
            }
            
            // Warn if trial ending soon (< 3 days)
            $daysLeft = $tenant->getDaysUntilExpiry();
            if ($daysLeft <= 3 && $daysLeft > 0) {
                Log::warning("Tenant {$tenant->subdomain} trial ending in {$daysLeft} days");
            }
        }
        
        // Check subscription expiry
        if ($tenant->status === 'active') {
            if ($tenant->subscription_ends_at < now()) {
                // Auto-update to expired
                $tenant->update(['status' => 'expired']);
                
                return response()->json([
                    'error' => 'Subscription expired',
                    'message' => 'Your subscription has expired. Please renew to continue.',
                    'expired_at' => $tenant->subscription_ends_at->format('d M Y H:i'),
                    'days_expired' => now()->diffInDays($tenant->subscription_ends_at),
                    'available_plans' => $this->getAvailablePlans(),
                ], 403);
            }
            
            // Warn if subscription ending soon (< 7 days)
            $daysLeft = $tenant->getDaysUntilExpiry();
            if ($daysLeft <= 7 && $daysLeft > 0) {
                Log::warning("Tenant {$tenant->subdomain} subscription ending in {$daysLeft} days");
            }
        }
        
        // Check expired status
        if ($tenant->status === 'expired') {
            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Your subscription has expired. Please renew to continue.',
                'available_plans' => $this->getAvailablePlans(),
            ], 403);
        }
        
        return null; // All checks passed
    }
    
    /**
     * Get available subscription plans
     */
    protected function getAvailablePlans(): array
    {
        return \App\Models\SubscriptionPlan::active()
            ->ordered()
            ->get()
            ->map(fn($plan) => [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'duration' => $plan->duration_label,
                'savings' => $plan->savings > 0 ? "Hemat Rp " . number_format($plan->savings, 0, ',', '.') : null,
            ])
            ->toArray();
    }
}
