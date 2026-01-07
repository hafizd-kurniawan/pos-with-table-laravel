<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('superadmin.login');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $remember = $request->filled('remember');
        
        // CRITICAL: Find user WITHOUT global scope to allow super admin login
        $user = \App\Models\User::withoutGlobalScope('tenant')
            ->where('email', $credentials['email'])
            ->first();
        
        if (!$user) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Invalid credentials');
        }
        
        // Check if super admin (tenant_id is null)
        if ($user->tenant_id !== null) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Access denied. Super admin only.');
        }
        
        // Verify password
        if (!\Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Invalid credentials');
        }
        
        // Login the user WITH remember me support
        Auth::login($user, $remember);
        $request->session()->regenerate();
        
        // Clear any old tenant context
        session()->forget(['tenant_id', 'tenant']);
        
        return redirect()->intended(route('superadmin.dashboard'));
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('superadmin.login');
    }
}
