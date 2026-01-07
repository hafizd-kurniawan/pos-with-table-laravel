<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('status', 'trial')->count(),
            'expired_tenants' => Tenant::where('status', 'expired')->count(),
        ];
        
        // Recent tenants
        $recentTenants = Tenant::latest()->take(5)->get();
        
        // Tenants expiring soon (< 7 days)
        $expiringSoon = Tenant::where(function($query) {
            $query->where('status', 'trial')
                  ->where('trial_ends_at', '<=', now()->addDays(7))
                  ->where('trial_ends_at', '>=', now());
        })->orWhere(function($query) {
            $query->where('status', 'active')
                  ->where('subscription_ends_at', '<=', now()->addDays(7))
                  ->where('subscription_ends_at', '>=', now());
        })->get();
        
        return view('superadmin.dashboard', compact('stats', 'recentTenants', 'expiringSoon'));
    }
}
