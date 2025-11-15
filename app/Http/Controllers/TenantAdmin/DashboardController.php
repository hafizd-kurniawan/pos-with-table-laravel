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
    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        
        // Statistics for tenant
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_orders' => Order::count(),
            'total_tables' => Table::count(),
            'total_staff' => User::where('tenant_id', $tenant->id)->count(),
        ];
        
        // Today's orders
        $todayOrders = Order::whereDate('created_at', today())->get();
        $todayRevenue = $todayOrders->sum('total_amount');
        
        // Recent orders (latest 5)
        $recentOrders = Order::with(['table'])
            ->latest()
            ->take(5)
            ->get();
        
        // Low stock products (< 10)
        $lowStockProducts = Product::where('stock', '<', 10)
            ->where('stock', '>', 0)
            ->orderBy('stock', 'asc')
            ->take(5)
            ->get();
        
        return view('tenantadmin.dashboard', compact(
            'tenant',
            'stats',
            'todayOrders',
            'todayRevenue',
            'recentOrders',
            'lowStockProducts'
        ));
    }
    
    public function expired()
    {
        return view('tenantadmin.expired');
    }
}
