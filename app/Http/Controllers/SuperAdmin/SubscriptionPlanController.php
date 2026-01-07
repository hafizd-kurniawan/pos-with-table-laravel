<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->get();
        return view('superadmin.plans.index', compact('plans'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'duration_label' => 'required|string|max:50',
            'max_products' => 'nullable|integer|min:0',
            'max_orders_per_day' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);
        
        SubscriptionPlan::create($validated);
        
        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Subscription plan created successfully');
    }
    
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'duration_label' => 'required|string|max:50',
            'max_products' => 'nullable|integer|min:0',
            'max_orders_per_day' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);
        
        $plan->update($validated);
        
        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Subscription plan updated successfully');
    }
    
    public function destroy(SubscriptionPlan $plan)
    {
        // Check if any tenant using this plan
        $tenantsCount = \App\Models\Tenant::where('subscription_plan', $plan->slug)->count();
        
        if ($tenantsCount > 0) {
            return redirect()->back()
                ->with('error', "Cannot delete plan. {$tenantsCount} tenants are using this plan.");
        }
        
        $plan->delete();
        
        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Subscription plan deleted successfully');
    }
}
