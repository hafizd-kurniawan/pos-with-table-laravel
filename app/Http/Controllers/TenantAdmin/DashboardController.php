<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        
        // Date Filter
        $startDate = $request->has('start_date') 
            ? \Carbon\Carbon::parse($request->start_date) 
            : now()->startOfMonth();
            
        $end = $request->has('end_date') 
            ? \Carbon\Carbon::parse($request->end_date) 
            : now()->endOfMonth();
            
        // Ensure end date is not in future for accurate "current" reporting, 
        // but allow it if user explicitly selects it. 
        // Actually, for reports, end of month is fine.
        $endDate = $end;

        // Statistics for tenant (Basic Counts)
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_orders' => Order::count(),
            'total_tables' => Table::count(),
            'total_staff' => User::where('tenant_id', $tenant->id)->count(),
        ];
        
        // Rich Dashboard Stats via Service
        // We explicitly pass tenant->id to ensure service uses correct context
        $dashboardService = new \App\Services\DashboardService($tenant->id);
        
        $dashboardData = [
            'sales_summary' => $dashboardService->getSalesSummary($startDate, $endDate),
            'today_sales' => $dashboardService->getTodaySales(),
            'sales_trend' => $dashboardService->getSalesTrend($startDate, $endDate),
            'sales_by_payment' => $dashboardService->getSalesByPaymentMethod($startDate, $endDate),
            'sales_by_type' => $dashboardService->getSalesByOrderType($startDate, $endDate),
            'top_products' => $dashboardService->getTopProducts(5, $startDate, $endDate),
            'recent_orders' => $dashboardService->getRecentOrders(10),
            'inventory_stats' => $dashboardService->getInventoryStats(),
            'critical_alerts' => $dashboardService->getCriticalAlerts(5),
        ];
        
        return view('tenantadmin.dashboard', compact(
            'tenant',
            'stats',
            'dashboardData',
            'startDate',
            'endDate'
        ));
    }
    
    public function expired()
    {
        return view('tenantadmin.expired');
    }
}
