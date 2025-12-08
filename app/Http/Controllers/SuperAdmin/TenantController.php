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
    
    public function show(Request $request, Tenant $tenant)
    {
        $tenant->load('users');
        
        // Date Filtering
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : today();
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : today();
        
        // Get data counts for this tenant
        app()->instance('tenant', $tenant);
        
        // Basic Stats
        $stats = [
            'products' => \App\Models\Product::count(),
            'categories' => \App\Models\Category::count(),
            'orders' => \App\Models\Order::count(),
            'tables' => \App\Models\Table::count(),
            'users' => \App\Models\User::count(),
        ];

        // Rich Dashboard Stats
        $dashboardService = new \App\Services\DashboardService($tenant->id);
        $dashboardData = [
            'sales_summary' => $dashboardService->getSalesSummary($startDate, $endDate),
            'sales_trend' => $dashboardService->getSalesTrend($startDate, $endDate),
            'sales_by_payment' => $dashboardService->getSalesByPaymentMethod($startDate, $endDate),
            'sales_by_type' => $dashboardService->getSalesByOrderType($startDate, $endDate),
            'top_products' => $dashboardService->getTopProducts(5, $startDate, $endDate),
            'recent_orders' => $dashboardService->getRecentOrders(10), // Recent orders always show latest
            'inventory_stats' => $dashboardService->getInventoryStats(), // Inventory is always current state
            'critical_alerts' => $dashboardService->getCriticalAlerts(5), // Alerts are always current state
        ];
        
        app()->forgetInstance('tenant');
        
        // Get active subscription plans for dropdown
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return view('superadmin.tenants.show', compact('tenant', 'stats', 'plans', 'dashboardData', 'startDate', 'endDate'));
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
            'days' => 'required|integer|min:-365|max:365',
        ]);
        
        $days = (int) $validated['days'];
        
        if ($days > 0) {
            $tenant->extendTrial($days);
            return redirect()->back()->with('success', "Trial extended by {$days} days");
        } else {
            // Reduce trial (negative days)
            $reduceDays = abs($days);
            
            if ($tenant->trial_ends_at) {
                $newTrialEnd = $tenant->trial_ends_at->subDays($reduceDays);
                
                // Prevent setting trial end before trial start
                if ($newTrialEnd < $tenant->trial_starts_at) {
                    $newTrialEnd = $tenant->trial_starts_at;
                }
                
                $tenant->update([
                    'trial_ends_at' => $newTrialEnd,
                ]);
                
                // Check if trial already expired
                if ($newTrialEnd < now()) {
                    $tenant->update(['status' => 'expired']);
                }
            }
            
            return redirect()->back()->with('success', "Trial reduced by {$reduceDays} days");
        }
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

    public function exportPdf(Request $request, Tenant $tenant)
    {
        // Date Filtering
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : today();
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : today();
        
        // Get data counts for this tenant
        app()->instance('tenant', $tenant);
        
        // Rich Dashboard Stats
        $dashboardService = new \App\Services\DashboardService($tenant->id);
        $dashboardData = [
            'sales_summary' => $dashboardService->getSalesSummary($startDate, $endDate),
            'sales_trend' => $dashboardService->getSalesTrend($startDate, $endDate),
            'sales_by_payment' => $dashboardService->getSalesByPaymentMethod($startDate, $endDate),
            'sales_by_type' => $dashboardService->getSalesByOrderType($startDate, $endDate),
            'top_products' => $dashboardService->getTopProducts(10, $startDate, $endDate), // Top 10 for PDF
            'recent_orders' => $dashboardService->getRecentOrders(20), // More recent orders for PDF
            'inventory_stats' => $dashboardService->getInventoryStats(),
            'critical_alerts' => $dashboardService->getCriticalAlerts(10), // More alerts for PDF
        ];
        
        app()->forgetInstance('tenant');
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('superadmin.tenants.pdf', compact('tenant', 'dashboardData', 'startDate', 'endDate'));
        
        return $pdf->download("report-{$tenant->subdomain}-" . now()->format('YmdHis') . ".pdf");
    }
}
