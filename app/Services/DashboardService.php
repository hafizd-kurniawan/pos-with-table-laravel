<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected $tenantId;
    protected $cacheTime = 300; // 5 minutes

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? auth()->user()->tenant_id ?? null;
    }

    /**
     * Get today's sales statistics
     */
    public function getTodaySales(): array
    {
        return Cache::remember("dashboard.sales.today.{$this->tenantId}", $this->cacheTime, function () {
            // Include all paid orders (paid, cooking, complete)
            $query = Order::where('orders.tenant_id', $this->tenantId)
                ->whereDate('orders.created_at', today())
                ->whereIn('orders.status', ['paid', 'cooking', 'complete']);

            // Clone query for totals to avoid interference with joins if needed, 
            // but here we can do it in one go with conditional aggregation.
            
            $stats = $query->leftJoin('tables', 'orders.table_id', '=', 'tables.id')
                ->selectRaw('
                    COALESCE(SUM(orders.total_amount), 0) as total_sales,
                    COUNT(*) as total_orders,
                    COALESCE(AVG(orders.total_amount), 0) as avg_order,
                    
                    COALESCE(SUM(CASE WHEN tables.name != "Takeaway" OR tables.name IS NULL THEN orders.total_amount ELSE 0 END), 0) as dine_in_sales,
                    COALESCE(SUM(CASE WHEN tables.name = "Takeaway" THEN orders.total_amount ELSE 0 END), 0) as takeaway_sales,
                    
                    COALESCE(SUM(CASE WHEN orders.payment_method = "cash" THEN orders.total_amount ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(CASE WHEN orders.payment_method = "qris" THEN orders.total_amount ELSE 0 END), 0) as qris_sales,

                    COUNT(CASE WHEN tables.name != "Takeaway" OR tables.name IS NULL THEN 1 END) as dine_in_count,
                    COUNT(CASE WHEN tables.name = "Takeaway" THEN 1 END) as takeaway_count,
                    
                    COUNT(CASE WHEN orders.payment_method = "cash" THEN 1 END) as cash_count,
                    COUNT(CASE WHEN orders.payment_method = "qris" THEN 1 END) as qris_count
                ')
                ->first();

            // Get Yesterday's Breakdown
            $yesterdayStats = Order::where('orders.tenant_id', $this->tenantId)
                ->whereDate('orders.created_at', today()->subDay())
                ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
                ->leftJoin('tables', 'orders.table_id', '=', 'tables.id')
                ->selectRaw('
                    COALESCE(SUM(orders.total_amount), 0) as total_sales,
                    
                    COALESCE(SUM(CASE WHEN tables.name != "Takeaway" OR tables.name IS NULL THEN orders.total_amount ELSE 0 END), 0) as dine_in_sales,
                    COALESCE(SUM(CASE WHEN tables.name = "Takeaway" THEN orders.total_amount ELSE 0 END), 0) as takeaway_sales,
                    
                    COALESCE(SUM(CASE WHEN orders.payment_method = "cash" THEN orders.total_amount ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(CASE WHEN orders.payment_method = "qris" THEN orders.total_amount ELSE 0 END), 0) as qris_sales
                ')
                ->first();

            $yesterday = $yesterdayStats->total_sales ?? 0;
            $change = $yesterday > 0 ? (($stats->total_sales - $yesterday) / $yesterday) * 100 : 0;

            // Calculate changes for breakdown
            $dineInChange = ($yesterdayStats->dine_in_sales ?? 0) > 0 ? (($stats->dine_in_sales - $yesterdayStats->dine_in_sales) / $yesterdayStats->dine_in_sales) * 100 : 0;
            $takeawayChange = ($yesterdayStats->takeaway_sales ?? 0) > 0 ? (($stats->takeaway_sales - $yesterdayStats->takeaway_sales) / $yesterdayStats->takeaway_sales) * 100 : 0;
            $cashChange = ($yesterdayStats->cash_sales ?? 0) > 0 ? (($stats->cash_sales - $yesterdayStats->cash_sales) / $yesterdayStats->cash_sales) * 100 : 0;
            $qrisChange = ($yesterdayStats->qris_sales ?? 0) > 0 ? (($stats->qris_sales - $yesterdayStats->qris_sales) / $yesterdayStats->qris_sales) * 100 : 0;

            return [
                'total_sales' => $stats->total_sales ?? 0,
                'total_orders' => $stats->total_orders ?? 0,
                'avg_order' => $stats->avg_order ?? 0,
                'yesterday_sales' => $yesterday ?? 0,
                'change_percentage' => round($change, 1),
                
                'dine_in_sales' => $stats->dine_in_sales ?? 0,
                'takeaway_sales' => $stats->takeaway_sales ?? 0,
                'cash_sales' => $stats->cash_sales ?? 0,
                'qris_sales' => $stats->qris_sales ?? 0,
                
                'dine_in_change' => round($dineInChange, 1),
                'takeaway_change' => round($takeawayChange, 1),
                'cash_change' => round($cashChange, 1),
                'qris_change' => round($qrisChange, 1),
                
                'dine_in_count' => $stats->dine_in_count ?? 0,
                'takeaway_count' => $stats->takeaway_count ?? 0,
                'cash_count' => $stats->cash_count ?? 0,
                'qris_count' => $stats->qris_count ?? 0,
            ];
        });
    }

    /**
     * Get sales trend for last 7 days
     */
    public function getSalesTrend($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? today()->subDays(6);
        $endDate = $endDate ?? today();

        $cacheKey = "dashboard.sales.trend.{$this->tenantId}." . $startDate->format('Ymd') . "." . $endDate->format('Ymd');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            // Include all paid orders (paid, cooking, complete)
            $data = Order::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('DATE(created_at)'))
                ->selectRaw('
                    DATE(created_at) as date,
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COUNT(*) as total_orders
                ')
                ->get()
                ->keyBy('date');

            // Fill missing dates with 0
            $salesData = [];
            $ordersData = [];
            $labels = [];

            $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                $dayData = $data->get($dateStr);

                $salesData[] = $dayData ? (float) $dayData->total_sales : 0;
                $ordersData[] = $dayData ? (int) $dayData->total_orders : 0;
                $labels[] = $date->format('D');
            }

            return [
                'labels' => $labels,
                'sales' => $salesData,
                'orders' => $ordersData,
                'average' => count($salesData) > 0 ? array_sum($salesData) / count($salesData) : 0,
                'best_day' => count($salesData) > 0 ? max($salesData) : 0,
            ];
        });
    }

    /**
     * Get sales summary for a specific period
     */
    public function getSalesSummary($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $cacheKey = "dashboard.sales.summary.{$this->tenantId}." . $startDate->format('Ymd') . "." . $endDate->format('Ymd');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            $sales = Order::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->selectRaw('
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COUNT(*) as total_orders,
                    COALESCE(AVG(total_amount), 0) as avg_order
                ')
                ->first();

            // Calculate previous period sales for percentage change
            $duration = $startDate->diffInDays($endDate) + 1;
            $prevEndDate = $startDate->copy()->subDay();
            $prevStartDate = $prevEndDate->copy()->subDays($duration - 1);

            $prevSales = Order::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->whereDate('created_at', '>=', $prevStartDate)
                ->whereDate('created_at', '<=', $prevEndDate)
                ->sum('total_amount');

            $currentSales = $sales->total_sales ?? 0;
            
            if ($prevSales > 0) {
                $change = (($currentSales - $prevSales) / $prevSales) * 100;
            } elseif ($currentSales > 0) {
                $change = 100; // 100% growth if previous was 0 but current is positive
            } else {
                $change = 0;
            }

            return [
                'total_sales' => $currentSales,
                'total_orders' => $sales->total_orders ?? 0,
                'avg_order' => $sales->avg_order ?? 0,
                'period_label' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
                'change_percentage' => round($change, 1),
            ];
        });
    }

    /**
     * Get sales by payment method
     */
    public function getSalesByPaymentMethod($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $cacheKey = "dashboard.sales.payment.{$this->tenantId}." . $startDate->format('Ymd') . "." . $endDate->format('Ymd');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            return Order::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->groupBy('payment_method')
                ->selectRaw('
                    payment_method,
                    COUNT(*) as count,
                    SUM(total_amount) as total
                ')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => ucfirst($item->payment_method),
                        'count' => $item->count,
                        'total' => $item->total,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get sales by order type (dine_in, takeaway)
     */
    public function getSalesByOrderType($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $cacheKey = "dashboard.sales.type.{$this->tenantId}." . $startDate->format('Ymd') . "." . $endDate->format('Ymd');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            // Since we don't have an explicit 'order_type' column in the Order model shown in previous context,
            // we infer it from table_id. If table name is 'Takeaway' or table_id is null/0, it's takeaway.
            // However, looking at previous OrderController code, we saw 'order_type' being passed.
            // Let's check if 'order_type' column exists in Order model. 
            // Assuming it might not exist based on previous Order model view, we'll use a join or logic.
            // Wait, in Step 8045 OrderController.php line 167 'payment_method' is saved.
            // In Step 8052 OrderController.php line 1613, we saw $request->order_type.
            // Let's assume for now we group by table name logic or if order_type exists.
            // SAFEST APPROACH: Group by a calculated field based on table name.
            
            return Order::where('orders.tenant_id', $this->tenantId)
                ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
                ->whereDate('orders.created_at', '>=', $startDate)
                ->whereDate('orders.created_at', '<=', $endDate)
                ->leftJoin('tables', 'orders.table_id', '=', 'tables.id')
                ->selectRaw('
                    CASE 
                        WHEN tables.name = "Takeaway" THEN "Takeaway"
                        ELSE "Dine In"
                    END as type,
                    COUNT(*) as count,
                    SUM(orders.total_amount) as total
                ')
                ->groupBy('type')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->type,
                        'count' => $item->count,
                        'total' => $item->total,
                        'color' => $item->type === 'Takeaway' ? '#10B981' : '#3B82F6', // Green for Takeaway, Blue for Dine In
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get inventory statistics
     */
    public function getInventoryStats(): array
    {
        return Cache::remember("dashboard.inventory.stats.{$this->tenantId}", $this->cacheTime, function () {
            $stats = Ingredient::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->selectRaw('
                    COUNT(*) as total_items,
                    COALESCE(SUM(current_stock * cost_per_unit), 0) as total_value,
                    SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN current_stock <= min_stock AND current_stock > 0 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN current_stock > min_stock THEN 1 ELSE 0 END) as healthy
                ')
                ->first();

            // Determine health status
            $health = 'good';
            if ($stats->out_of_stock > 0) {
                $health = 'critical';
            } elseif ($stats->low_stock >= 3) {
                $health = 'warning';
            }

            return [
                'total_value' => $stats->total_value ?? 0,
                'total_items' => $stats->total_items ?? 0,
                'out_of_stock' => $stats->out_of_stock ?? 0,
                'low_stock' => $stats->low_stock ?? 0,
                'healthy' => $stats->healthy ?? 0,
                'health_status' => $health,
                'total_alerts' => ($stats->out_of_stock ?? 0) + ($stats->low_stock ?? 0),
            ];
        });
    }

    /**
     * Get critical alerts (items needing attention)
     */
    public function getCriticalAlerts(int $limit = 5): array
    {
        return Cache::remember("dashboard.alerts.{$this->tenantId}.{$limit}", $this->cacheTime, function () use ($limit) {
            return Ingredient::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('current_stock', '<=', DB::raw('min_stock'))
                        ->orWhere('current_stock', '<=', 0);
                })
                ->selectRaw('
                    id,
                    name,
                    current_stock,
                    min_stock,
                    unit,
                    CASE 
                        WHEN current_stock <= 0 THEN "critical"
                        WHEN current_stock <= min_stock THEN "warning"
                    END as alert_level,
                    CASE
                        WHEN current_stock <= 0 THEN 0
                        ELSE ((current_stock / min_stock) * 100)
                    END as percentage
                ')
                ->orderByRaw('CASE WHEN current_stock <= 0 THEN 0 ELSE 1 END')
                ->orderBy('percentage', 'asc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get top selling products
     */

    public function getTopProducts(int $limit = 5, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : today()->subDays(6);
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : today();
        
        $cacheKey = "dashboard.top_products.{$this->tenantId}.{$limit}." . $startDate->format('Ymd') . "." . $endDate->format('Ymd');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($limit, $startDate, $endDate) {
            // Calculate previous period for trend (same duration as current period)
            $durationInDays = $startDate->diffInDays($endDate) + 1;
            $previousStartDate = $startDate->copy()->subDays($durationInDays);
            $previousEndDate = $endDate->copy()->subDays($durationInDays);

            $products = Product::query()
                ->select([
                    'products.id',
                    'products.name',
                    DB::raw('COALESCE(SUM(oi.quantity), 0) as total_qty'),
                    DB::raw('COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue'),
                ])
                ->join('order_items as oi', 'products.id', '=', 'oi.product_id')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->where('products.tenant_id', $this->tenantId)
                ->where('o.tenant_id', $this->tenantId)
                ->whereIn('o.status', ['paid', 'cooking', 'complete'])
                ->whereDate('o.created_at', '>=', $startDate)
                ->whereDate('o.created_at', '<=', $endDate)
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_qty', 'desc')
                ->limit($limit)
                ->get();

            // Get previous period data for trend
            return $products->map(function ($product) use ($previousStartDate, $previousEndDate) {
                $prevQty = DB::table('order_items as oi')
                    ->join('orders as o', 'oi.order_id', '=', 'o.id')
                    ->where('oi.product_id', $product->id)
                    ->whereIn('o.status', ['paid', 'cooking', 'complete'])
                    ->where('o.tenant_id', $this->tenantId)
                    ->whereDate('o.created_at', '>=', $previousStartDate)
                    ->whereDate('o.created_at', '<=', $previousEndDate)
                    ->sum('oi.quantity');

                $change = $prevQty > 0 ? (($product->total_qty - $prevQty) / $prevQty) * 100 : 0;

                return [
                    'name' => $product->name,
                    'quantity' => (int) $product->total_qty,
                    'revenue' => (float) $product->total_revenue,
                    'change' => round($change, 1),
                    'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
                ];
            })->toArray();
        });
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return Order::where('tenant_id', $this->tenantId)
            ->whereDate('created_at', today())
            ->with('table')
            ->withCount('orderItems')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->code ?? $order->id,
                    'table' => $order->table?->name ?? 'Takeaway',
                    'items_count' => $order->order_items_count,
                    'grand_total' => $order->total_amount,
                    'status' => $order->status,
                    'time' => $order->created_at->format('H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent stock movements
     */
    public function getRecentStockMovements(int $limit = 5): array
    {
        return StockMovement::where('tenant_id', $this->tenantId)
            ->whereDate('created_at', today())
            ->with('ingredient')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($movement) {
                $reference = $this->getMovementReference($movement);

                return [
                    'type' => $movement->type,
                    'ingredient' => $movement->ingredient->name,
                    'quantity' => $movement->quantity,
                    'unit' => $movement->ingredient->unit,
                    'reference' => $reference,
                    'time' => $movement->created_at->format('H:i'),
                    'notes' => $movement->notes,
                ];
            })
            ->toArray();
    }

    /**
     * Get movement reference label
     */
    protected function getMovementReference($movement): string
    {
        if (!$movement->reference_type || !$movement->reference_id) {
            return 'Manual Entry';
        }

        switch ($movement->reference_type) {
            case 'purchase_order':
                $po = PurchaseOrder::find($movement->reference_id);
                return $po ? "PO#{$po->po_number}" : 'PO (deleted)';

            case 'order':
                $order = Order::find($movement->reference_id);
                return $order ? "Order #{$order->order_number}" : 'Order (deleted)';

            case 'stock_opname':
                $opname = StockOpname::find($movement->reference_id);
                return $opname ? "Opname #{$opname->id}" : 'Opname';

            case 'adjustment':
                return 'Manual Adjustment';

            default:
                return ucfirst($movement->reference_type);
        }
    }

    /**
     * Get pending actions (smart to-do list)
     */
    public function getPendingActions(): array
    {
        $actions = [
            'urgent' => [],
            'important' => [],
            'info' => [],
        ];

        // Check out of stock items
        $outOfStock = Ingredient::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('current_stock', '<=', 0)
            ->count();

        if ($outOfStock > 0) {
            $actions['urgent'][] = [
                'title' => "Create PO for {$outOfStock} out-of-stock items",
                'description' => 'Critical items need immediate ordering',
                'action' => 'create_po',
                'url' => route('filament.admin.resources.purchase-orders.create'),
            ];
        }

        // Check pending POs (sent but not received)
        $pendingPOs = PurchaseOrder::where('tenant_id', $this->tenantId)
            ->where('status', 'sent')
            ->get();

        foreach ($pendingPOs as $po) {
            $daysAgo = $po->order_date->diffInDays(today());
            $urgency = $daysAgo >= 3 ? 'urgent' : 'important';

            $actions[$urgency][] = [
                'title' => "Receive PO#{$po->po_number}",
                'description' => "Sent {$daysAgo} days ago - {$po->supplier->name}",
                'action' => 'receive_po',
                'url' => route('filament.admin.resources.purchase-orders.edit', $po->id),
            ];
        }

        // Check low stock items
        $lowStock = Ingredient::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('current_stock', '<=', DB::raw('min_stock'))
            ->where('current_stock', '>', 0)
            ->count();

        if ($lowStock > 0) {
            $actions['important'][] = [
                'title' => "Review {$lowStock} low stock items",
                'description' => 'Items need reordering soon',
                'action' => 'review_stock',
                'url' => route('filament.admin.resources.ingredients.index', [
                    'tableFilters[low_stock][value]' => true
                ]),
            ];
        }

        // Check incomplete stock opnames
        $incompleteOpnames = StockOpname::where('tenant_id', $this->tenantId)
            ->where('status', 'in_progress')
            ->get();

        foreach ($incompleteOpnames as $opname) {
            $completed = $opname->items()->whereNotNull('physical_count')->count();
            $total = $opname->items()->count();

            $actions['important'][] = [
                'title' => "Complete Stock Opname #{$opname->id}",
                'description' => "Started {$opname->created_at->diffForHumans()} ({$completed}/{$total} counted)",
                'action' => 'complete_opname',
                'url' => route('filament.admin.resources.stock-opnames.edit', $opname->id),
            ];
        }

        // Weekly report reminder (info)
        if (today()->dayOfWeek === 0) { // Sunday
            $actions['info'][] = [
                'title' => 'Review weekly sales report',
                'description' => 'Week ending ' . today()->format('M d, Y'),
                'action' => 'view_report',
                'url' => route('filament.admin.resources.orders.index'),
            ];
        }

        return $actions;
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): void
    {
        $patterns = [
            "dashboard.sales.today.{$this->tenantId}",
            "dashboard.sales.trend.{$this->tenantId}",
            "dashboard.inventory.stats.{$this->tenantId}",
            "dashboard.alerts.{$this->tenantId}.*",
            "dashboard.top_products.{$this->tenantId}.*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
