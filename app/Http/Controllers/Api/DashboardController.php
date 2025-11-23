<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get today's dashboard summary for Flutter app
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function todaySummary(Request $request)
    {
        $today = Carbon::today();
        $tenantId = auth()->user()->tenant_id;
        
        // Today's orders (exclude cancelled)
        $orders = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->get();
        
        // Calculate basic stats
        $totalSales = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $avgOrder = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        
        // Count unique customers (by phone number)
        $uniqueCustomers = $orders
            ->pluck('customer_phone')
            ->filter()
            ->unique()
            ->count();
        
        // If no phone tracking, estimate from orders
        if ($uniqueCustomers === 0 && $totalOrders > 0) {
            $uniqueCustomers = (int) ($totalOrders * 0.8); // Estimate: 80% unique
        }
        
        // Orders by type
        $ordersByType = $orders->groupBy('order_type')->map(function ($items) {
            return $items->count();
        });
        
        $dineIn = $ordersByType->get('dine_in', 0);
        $takeaway = $ordersByType->get('takeaway', 0);
        $delivery = $ordersByType->get('delivery', 0);
        
        // Top products today
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereDate('orders.created_at', $today)
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => (string) $item->total_revenue,
                ];
            });
        
        // Low stock alerts
        $lowStockCount = Product::where('tenant_id', $tenantId)
            ->where('stock', '<', 10)
            ->where('stock', '>', 0)
            ->count();
        
        // Pending orders (for kitchen)
        $pendingOrders = Order::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'paid'])
            ->count();
        
        // Get subscription info
        $tenant = auth()->user()->tenant;
        $subscriptionTier = $tenant->subscription_plan ?? 'free';
        
        // Check if can access full reports
        $canAccessReports = true; // Default for now
        if ($subscriptionTier === 'free' || $subscriptionTier === null) {
            // Check if they've used their free trial
            $reportsAccessed = $tenant->reports_accessed_count ?? 0;
            $canAccessReports = $reportsAccessed < 1;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'total_sales' => (int) $totalSales,
                    'total_orders' => $totalOrders,
                    'average_order' => (int) $avgOrder,
                    'unique_customers' => $uniqueCustomers,
                ],
                'order_types' => [
                    'dine_in' => $dineIn,
                    'takeaway' => $takeaway,
                    'delivery' => $delivery,
                ],
                'top_products' => $topProducts,
                'alerts' => [
                    'low_stock_count' => $lowStockCount,
                    'pending_orders' => $pendingOrders,
                ],
                'subscription' => [
                    'tier' => $subscriptionTier,
                    'can_access_full_reports' => $canAccessReports,
                ],
            ],
        ]);
    }
}
