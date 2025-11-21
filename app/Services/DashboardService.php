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

    public function __construct()
    {
        $this->tenantId = auth()->user()->tenant_id ?? null;
    }

    /**
     * Get today's sales statistics
     */
    public function getTodaySales(): array
    {
        return Cache::remember("dashboard.sales.today.{$this->tenantId}", $this->cacheTime, function () {
            // Include all paid orders (paid, cooking, complete) - exclude only pending/cancelled
            $today = Order::where('tenant_id', $this->tenantId)
                ->whereDate('created_at', today())
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->selectRaw('
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COUNT(*) as total_orders,
                    COALESCE(AVG(total_amount), 0) as avg_order
                ')
                ->first();

            $yesterday = Order::where('tenant_id', $this->tenantId)
                ->whereDate('created_at', today()->subDay())
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->sum('total_amount');

            $change = $yesterday > 0 ? (($today->total_sales - $yesterday) / $yesterday) * 100 : 0;

            return [
                'total_sales' => $today->total_sales ?? 0,
                'total_orders' => $today->total_orders ?? 0,
                'avg_order' => $today->avg_order ?? 0,
                'yesterday_sales' => $yesterday ?? 0,
                'change_percentage' => round($change, 1),
            ];
        });
    }

    /**
     * Get sales trend for last 7 days
     */
    public function getSalesTrend(): array
    {
        return Cache::remember("dashboard.sales.trend.{$this->tenantId}", $this->cacheTime, function () {
            // Include all paid orders (paid, cooking, complete)
            $data = Order::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['paid', 'cooking', 'complete'])
                ->whereDate('created_at', '>=', today()->subDays(6))
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

            for ($i = 6; $i >= 0; $i--) {
                $date = today()->subDays($i);
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
    public function getTopProducts(int $days = 7, int $limit = 5): array
    {
        return Cache::remember("dashboard.top_products.{$this->tenantId}.{$days}.{$limit}", $this->cacheTime, function () use ($days, $limit) {
            $currentPeriod = today()->subDays($days - 1);
            $previousPeriod = $currentPeriod->copy()->subDays($days);

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
                ->whereDate('o.created_at', '>=', $currentPeriod)
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_qty', 'desc')
                ->limit($limit)
                ->get();

            // Get previous period data for trend
            return $products->map(function ($product) use ($previousPeriod, $days) {
                $prevQty = DB::table('order_items as oi')
                    ->join('orders as o', 'oi.order_id', '=', 'o.id')
                    ->where('oi.product_id', $product->id)
                    ->whereIn('o.status', ['paid', 'cooking', 'complete'])
                    ->where('o.tenant_id', $this->tenantId)
                    ->whereDate('o.created_at', '>=', $previousPeriod)
                    ->whereDate('o.created_at', '<', $previousPeriod->copy()->addDays($days))
                    ->sum('oi.quantity');

                $change = $prevQty > 0 ? (($product->total_qty - $prevQty) / $prevQty) * 100 : 0;

                return [
                    'name' => $product->name,
                    'quantity' => $product->total_qty,
                    'revenue' => $product->total_revenue,
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
