<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('superadmin.login')
                ->with('error', 'Please login as super admin');
        }
        
        // CRITICAL: Get user WITHOUT scope to avoid filtering super admin
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user) {
            auth()->logout();
            return redirect()->route('superadmin.login')
                ->with('error', 'User not found');
        }
        
        // Check if user is super admin (tenant_id is null)
        if ($user->tenant_id !== null) {
            auth()->logout();
            return redirect()->route('superadmin.login')
                ->with('error', 'Access denied. Super admin only.');
        }
        
        return $next($request);
    }
}
