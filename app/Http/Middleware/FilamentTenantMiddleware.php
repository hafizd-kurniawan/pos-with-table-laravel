<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FilamentTenantMiddleware
{
    /**
     * Handle an incoming request for Filament Admin Panel.
     * 
     * This middleware ensures:
     * 1. User is authenticated
     * 2. User has tenant_id (NOT super admin)
     * 3. Sets tenant context for query scoping
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if request is for superadmin routes
        if ($request->is('superadmin*')) {
            return $next($request);
        }
        
        // Skip for login/logout routes to prevent loops
        // Also skip for CSS/JS assets
        if ($request->is('admin/login') || 
            $request->is('admin/logout') || 
            $request->is('livewire*') ||
            $request->is('css/*') ||
            $request->is('js/*') ||
            $request->is('filament/*')) {
            return $next($request);
        }
        
        // Only run for authenticated requests (after Filament's auth)
        if (Auth::check()) {
            // CRITICAL: Get user WITHOUT triggering scope
            $userId = Auth::id();
            $dbUser = \DB::table('users')->where('id', $userId)->first();
            
            if (!$dbUser) {
                // Don't logout - just redirect
                return redirect()->route('filament.admin.auth.login')
                    ->with('warning', 'Session expired. Please login again.');
            }
            
            // CRITICAL: Block super admin from accessing Filament tenant panel
            if ($dbUser->tenant_id === null || $dbUser->tenant_id === '' || empty($dbUser->tenant_id)) {
                // Only show warning once per session
                if (!session()->has('superadmin_warning_shown')) {
                    session()->put('superadmin_warning_shown', true);
                    
                    \Log::info('Super admin accessing tenant panel', [
                        'user_id' => $dbUser->id,
                        'email' => $dbUser->email,
                        'url' => $request->url(),
                    ]);
                    
                    return redirect('/superadmin/login')
                        ->with('warning', '⚠️ Access Denied! Super admins CANNOT access tenant panel. Use /superadmin/login instead.');
                }
                
                // Silent redirect if warning already shown
                return redirect('/superadmin/login');
            }
            
            // Clear superadmin warning flag
            session()->forget('superadmin_warning_shown');
            
            // Get user's tenant
            $tenant = \App\Models\Tenant::find($dbUser->tenant_id);
            
            if (!$tenant) {
                return redirect()->route('filament.admin.auth.login')
                    ->with('error', 'Your tenant account was not found. Please contact support.');
            }
            
            // Check tenant status - warn but allow access (read-only mode)
            if ($tenant->status === 'suspended') {
                // Just flash warning, don't redirect
                session()->flash('warning', 'Your account is suspended. Some features are disabled.');
            }
            
            if ($tenant->status === 'expired') {
                // Just flash warning, don't redirect or logout
                session()->flash('warning', 'Your subscription has expired. Please renew to access all features.');
            }
            
            // Set tenant context for automatic query scoping
            app()->instance('tenant', $tenant);
            app()->instance('current_tenant', $tenant);
        }
        
        return $next($request);
    }
}
