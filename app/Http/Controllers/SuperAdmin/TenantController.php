<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::latest()->paginate(20);
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return view('superadmin.tenants.index', compact('tenants', 'plans'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subdomain' => 'required|string|max:255|unique:tenants,subdomain|regex:/^[a-z0-9-]+$/',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:tenants,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'trial_days' => 'required|integer|min:1|max:365',
        ], [
            'subdomain.unique' => 'Subdomain already exists. Please use a different subdomain.',
            'subdomain.regex' => 'Subdomain must contain only lowercase letters, numbers, and hyphens.',
            'email.unique' => 'Email already exists. Each tenant must have a unique email address.',
        ]);
        
        // Create tenant
        $tenant = Tenant::create([
            'subdomain' => $validated['subdomain'],
            'business_name' => $validated['business_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'status' => 'trial',
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays((int) $validated['trial_days']),
        ]);
        
        // Create default admin user for tenant
        $password = Str::random(12);
        
        // Set current tenant context for user creation
        app()->instance('tenant', $tenant);
        
        $user = User::create([
            'name' => 'Admin ' . $tenant->business_name,
            'email' => $tenant->email,
            'password' => Hash::make($password),
            'tenant_id' => $tenant->id,
        ]);
        
        // Clear tenant context
        app()->forgetInstance('tenant');
        
        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', "Tenant created successfully!")
            ->with('password', $password)
            ->with('show_credentials', true);
    }
    
    public function show(Tenant $tenant)
    {
        $tenant->load('users');
        
        // Get data counts for this tenant
        app()->instance('tenant', $tenant);
        
        $stats = [
            'products' => \App\Models\Product::count(),
            'categories' => \App\Models\Category::count(),
            'orders' => \App\Models\Order::count(),
            'tables' => \App\Models\Table::count(),
            'users' => \App\Models\User::count(),
        ];
        
        app()->forgetInstance('tenant');
        
        // Get active subscription plans for dropdown
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return view('superadmin.tenants.show', compact('tenant', 'stats', 'plans'));
    }
    
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);
        
        $tenant->update($validated);
        
        return redirect()->back()->with('success', 'Tenant updated successfully');
    }
    
    public function extendTrial(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);
        
        $tenant->extendTrial((int) $validated['days']);
        
        return redirect()->back()->with('success', "Trial extended by {$validated['days']} days");
    }
    
    public function activateSubscription(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_slug' => 'required|exists:subscription_plans,slug',
            'duration_days' => 'required|integer|min:1',
        ]);
        
        $tenant->activateSubscription(
            $validated['plan_slug'],
            now()->addDays((int) $validated['duration_days'])
        );
        
        return redirect()->back()->with('success', 'Subscription activated successfully');
    }
    
    public function suspend(Tenant $tenant)
    {
        $tenant->suspend();
        
        return redirect()->back()->with('success', 'Tenant suspended');
    }
    
    public function reactivate(Tenant $tenant)
    {
        $tenant->reactivate();
        
        return redirect()->back()->with('success', 'Tenant reactivated');
    }
    
    public function destroy(Tenant $tenant)
    {
        // Safety check
        if ($tenant->subdomain === 'default') {
            return redirect()->back()->with('error', 'Cannot delete default tenant');
        }
        
        $tenant->delete();
        
        return redirect()->route('superadmin.tenants.index')
            ->with('success', 'Tenant deleted successfully');
    }
    
    /**
     * Reset tenant admin password
     */
    public function resetPassword(Tenant $tenant)
    {
        // Generate new random password
        $newPassword = \Illuminate\Support\Str::random(12);
        
        // Get tenant's admin user (first user)
        $adminUser = $tenant->users()->first();
        
        if (!$adminUser) {
            return back()->with('error', 'No admin user found for this tenant.');
        }
        
        // Update password
        $adminUser->update([
            'password' => \Illuminate\Support\Facades\Hash::make($newPassword),
        ]);
        
        return back()
            ->with('success', 'Password reset successfully!')
            ->with('new_password', $newPassword)
            ->with('show_password', true);
    }
}
