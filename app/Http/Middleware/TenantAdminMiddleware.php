<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantAdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Validates that:
     * 1. User is authenticated
     * 2. User has tenant_id (NOT null = not super admin)
     * 3. User belongs to a valid tenant
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for login/logout routes to prevent loops
        if ($request->is('tenantadmin/login') || $request->is('tenantadmin/logout')) {
            return $next($request);
        }
        
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('tenantadmin.login')
                ->with('warning', 'Please login to continue.');
        }
        
        $user = Auth::user();
        
        // Check if user is NOT super admin (must have tenant_id)
        if ($user->tenant_id === null) {
            // Don't logout - just redirect
            return redirect()->route('tenantadmin.login')
                ->with('warning', 'Access denied. Tenant admin only. Super admins should use /superadmin.');
        }
        
        // Check if user's tenant exists and is active
        $tenant = $user->tenant;
        if (!$tenant) {
            // Don't logout - just redirect
            return redirect()->route('tenantadmin.login')
                ->with('error', 'Your tenant account was not found. Please contact support.');
        }
        
        // Check tenant status - warn but allow access (read-only mode)
        if ($tenant->status === 'suspended') {
            // Just flash warning, don't redirect
            session()->flash('warning', 'Your account is suspended. Some features are disabled.');
        }
        
        if ($tenant->status === 'expired') {
            // Just flash warning, don't redirect
            session()->flash('warning', 'Your subscription has expired. Please renew to access all features.');
        }
        
        // Set current tenant in app container for easy access
        app()->instance('current_tenant', $tenant);
        
        return $next($request);
    }
}
