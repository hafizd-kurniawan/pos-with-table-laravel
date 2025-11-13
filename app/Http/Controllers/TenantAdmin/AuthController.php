<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('tenantadmin.login');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Check if tenant admin (tenant_id is NOT null)
            if ($user->tenant_id === null) {
                Auth::logout();
                return back()->with('error', 'Access denied. Tenant admin only. Super admins should use /superadmin.');
            }
            
            // Check if tenant is active
            $tenant = $user->tenant;
            if (!$tenant) {
                Auth::logout();
                return back()->with('error', 'Your tenant account was not found. Please contact support.');
            }
            
            if ($tenant->status === 'suspended') {
                Auth::logout();
                return back()->with('error', 'Your account has been suspended. Please contact support.');
            }
            
            $request->session()->regenerate();
            return redirect()->intended(route('tenantadmin.dashboard'));
        }
        
        return back()->with('error', 'Invalid credentials');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('tenantadmin.login');
    }
}
