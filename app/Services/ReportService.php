<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DailySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Generate daily summary for a specific date and tenant
     * 
     * @param int $tenantId
     * @param string $date (Y-m-d format)
     * @param bool $force Force regenerate even if exists
     * @return DailySummary
     */
    public function generateDailySummary(int $tenantId, string $date, bool $force = false): DailySummary
    {
        $dateObj = Carbon::parse($date);
        
        // Check if already exists
        $existing = DailySummary::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('date', $dateObj->format('Y-m-d'))
            ->first();
        
        if ($existing && !$force) {
            return $existing;
        }
        
        // Calculate summary from orders
        $summary = $this->calculateDailySummary($tenantId, $dateObj);
        
        // Save or update
        if ($existing) {
            $existing->update($summary);
            return $existing->fresh();
        }
        
        $summary['tenant_id'] = $tenantId;
        $summary['date'] = $dateObj->format('Y-m-d');
        
        return DailySummary::create($summary);
    }
    
    /**
     * Calculate daily summary from orders
     */
    protected function calculateDailySummary(int $tenantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        // Get all orders for the day (include paid, cooking, complete)
        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->get();
        
        // Calculate totals
        $totalOrders = $orders->count();
        $grossSales = $orders->sum('subtotal'); // Before discount
        $totalDiscount = $orders->sum('discount_amount');
        $subtotal = $orders->sum('subtotal');
        $totalTax = $orders->sum('tax_amount');
        $totalService = $orders->sum('service_charge_amount');
        $netSales = $orders->sum('total_amount');
        
        // Count unique customers
        $totalCustomers = $orders->whereNotNull('customer_name')->unique('customer_name')->count();
        if ($totalCustomers == 0) {
            $totalCustomers = $totalOrders; // Assume each order is one customer if no names
        }
        
        // Count total items sold
        $totalItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->sum('order_items.quantity');
        
        // Payment method breakdown
        $cashOrders = $orders->where('payment_method', 'cash');
        $qrisOrders = $orders->where('payment_method', 'qris');
        $gopayOrders = $orders->where('payment_method', 'gopay');
        
        return [
            'total_orders' => $totalOrders,
            'total_items' => $totalItems,
            'total_customers' => $totalCustomers,
            'gross_sales' => $grossSales,
            'total_discount' => $totalDiscount,
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'total_service' => $totalService,
            'net_sales' => $netSales,
            'cash_amount' => $cashOrders->sum('total_amount'),
            'cash_count' => $cashOrders->count(),
            'qris_amount' => $qrisOrders->sum('total_amount'),
            'qris_count' => $qrisOrders->count(),
            'gopay_amount' => $gopayOrders->sum('total_amount'),
            'gopay_count' => $gopayOrders->count(),
        ];
    }
    
    /**
     * Get daily summary (cached or calculate)
     */
    public function getDailySummary(int $tenantId, string $date): array
    {
        $dateObj = Carbon::parse($date);
        
        // Try to get from cache
        $cached = DailySummary::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('date', $dateObj->format('Y-m-d'))
            ->first();
        
        if ($cached) {
            return $this->formatDailySummary($cached);
        }
        
        // Calculate on-the-fly
        $summary = $this->calculateDailySummary($tenantId, $dateObj);
        $summary['date'] = $dateObj->format('Y-m-d');
        
        return $this->formatSummaryArray($summary, $dateObj);
    }
    
    /**
     * Get period summary (multiple days)
     */
    public function getPeriodSummary(int $tenantId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $days = (int) ($start->diffInDays($end) + 1);
        
        // Get orders for the period
        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->get();
        
        // Calculate aggregates
        $totalOrders = $orders->count();
        $grossSales = $orders->sum('subtotal');
        $totalDiscount = $orders->sum('discount_amount');
        $subtotal = $orders->sum('subtotal');
        $totalTax = $orders->sum('tax_amount');
        $totalService = $orders->sum('service_charge_amount');
        $netSales = $orders->sum('total_amount');
        
        // Count items
        $totalItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->sum('order_items.quantity');
        
        // Payment breakdown
        $cashOrders = $orders->where('payment_method', 'cash');
        $qrisOrders = $orders->where('payment_method', 'qris');
        $gopayOrders = $orders->where('payment_method', 'gopay');
        
        // Calculate comparison with previous period
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subDay();
        $previousNetSales = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->sum('total_amount');
        
        $growthAmount = $netSales - $previousNetSales;
        $growthPercentage = $previousNetSales > 0 
            ? (($growthAmount / $previousNetSales) * 100) 
            : 0;
        
        return [
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'days' => $days,
                'type' => $this->getPeriodType($days),
            ],
            'summary' => [
                'total_orders' => $totalOrders,
                'total_items' => $totalItems,
                'gross_sales' => (float) $grossSales,
                'total_discount' => (float) $totalDiscount,
                'subtotal' => (float) $subtotal,
                'total_tax' => (float) $totalTax,
                'total_service' => (float) $totalService,
                'net_sales' => (float) $netSales,
                'average_transaction' => $totalOrders > 0 ? ($netSales / $totalOrders) : 0,
            ],
            'comparison' => [
                'previous_period' => [
                    'start' => $previousStart->format('Y-m-d'),
                    'end' => $previousEnd->format('Y-m-d'),
                    'net_sales' => (float) $previousNetSales,
                ],
                'growth' => [
                    'amount' => (float) $growthAmount,
                    'percentage' => round($growthPercentage, 2),
                    'trend' => $growthAmount >= 0 ? 'up' : 'down',
                    'status' => $this->getGrowthStatus($growthPercentage),
                ],
            ],
            'payment_breakdown' => [
                [
                    'method' => 'cash',
                    'amount' => (float) $cashOrders->sum('total_amount'),
                    'count' => $cashOrders->count(),
                    'percentage' => $netSales > 0 ? round(($cashOrders->sum('total_amount') / $netSales) * 100, 2) : 0,
                ],
                [
                    'method' => 'qris',
                    'amount' => (float) $qrisOrders->sum('total_amount'),
                    'count' => $qrisOrders->count(),
                    'percentage' => $netSales > 0 ? round(($qrisOrders->sum('total_amount') / $netSales) * 100, 2) : 0,
                ],
                [
                    'method' => 'gopay',
                    'amount' => (float) $gopayOrders->sum('total_amount'),
                    'count' => $gopayOrders->count(),
                    'percentage' => $netSales > 0 ? round(($gopayOrders->sum('total_amount') / $netSales) * 100, 2) : 0,
                ],
            ],
        ];
    }
    
    /**
     * Get top selling products
     */
    public function getTopProducts(int $tenantId, string $startDate, string $endDate, int $limit = 10): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $topProducts = OrderItem::select(
                'products.id',
                'products.name',
                'products.category_id',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_sales')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->groupBy('products.id', 'products.name', 'products.category_id', 'categories.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
        
        $totalSales = $topProducts->sum('total_sales');
        
        return $topProducts->map(function($item) use ($totalSales) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category_name ?? 'Uncategorized',
                'quantity' => (int) $item->total_quantity,
                'total' => (float) $item->total_sales,
                'percentage' => $totalSales > 0 ? round(($item->total_sales / $totalSales) * 100, 2) : 0,
            ];
        })->toArray();
    }
    
    /**
     * Format daily summary model
     */
    protected function formatDailySummary(DailySummary $summary): array
    {
        return [
            'date' => $summary->date->format('Y-m-d'),
            'summary' => [
                'total_orders' => $summary->total_orders,
                'total_items' => $summary->total_items,
                'total_customers' => $summary->total_customers,
                'gross_sales' => (float) $summary->gross_sales,
                'total_discount' => (float) $summary->total_discount,
                'subtotal' => (float) $summary->subtotal,
                'total_tax' => (float) $summary->total_tax,
                'total_service' => (float) $summary->total_service,
                'net_sales' => (float) $summary->net_sales,
                'average_transaction' => $summary->total_orders > 0 
                    ? ($summary->net_sales / $summary->total_orders) 
                    : 0,
            ],
            'payment_breakdown' => [
                [
                    'method' => 'cash',
                    'amount' => (float) $summary->cash_amount,
                    'count' => $summary->cash_count,
                    'percentage' => $summary->net_sales > 0 
                        ? round(($summary->cash_amount / $summary->net_sales) * 100, 2) 
                        : 0,
                ],
                [
                    'method' => 'qris',
                    'amount' => (float) $summary->qris_amount,
                    'count' => $summary->qris_count,
                    'percentage' => $summary->net_sales > 0 
                        ? round(($summary->qris_amount / $summary->net_sales) * 100, 2) 
                        : 0,
                ],
                [
                    'method' => 'gopay',
                    'amount' => (float) $summary->gopay_amount,
                    'count' => $summary->gopay_count,
                    'percentage' => $summary->net_sales > 0 
                        ? round(($summary->gopay_amount / $summary->net_sales) * 100, 2) 
                        : 0,
                ],
            ],
            'meta' => [
                'is_closed' => $summary->is_closed,
                'closed_at' => $summary->closed_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }
    
    /**
     * Format summary array
     */
    protected function formatSummaryArray(array $summary, Carbon $date): array
    {
        $netSales = $summary['net_sales'];
        
        return [
            'date' => $date->format('Y-m-d'),
            'summary' => [
                'total_orders' => $summary['total_orders'],
                'total_items' => $summary['total_items'],
                'total_customers' => $summary['total_customers'],
                'gross_sales' => (float) $summary['gross_sales'],
                'total_discount' => (float) $summary['total_discount'],
                'subtotal' => (float) $summary['subtotal'],
                'total_tax' => (float) $summary['total_tax'],
                'total_service' => (float) $summary['total_service'],
                'net_sales' => (float) $netSales,
                'average_transaction' => $summary['total_orders'] > 0 
                    ? ($netSales / $summary['total_orders']) 
                    : 0,
            ],
            'payment_breakdown' => [
                [
                    'method' => 'cash',
                    'amount' => (float) $summary['cash_amount'],
                    'count' => $summary['cash_count'],
                    'percentage' => $netSales > 0 
                        ? round(($summary['cash_amount'] / $netSales) * 100, 2) 
                        : 0,
                ],
                [
                    'method' => 'qris',
                    'amount' => (float) $summary['qris_amount'],
                    'count' => $summary['qris_count'],
                    'percentage' => $netSales > 0 
                        ? round(($summary['qris_amount'] / $netSales) * 100, 2) 
                        : 0,
                ],
                [
                    'method' => 'gopay',
                    'amount' => (float) $summary['gopay_amount'],
                    'count' => $summary['gopay_count'],
                    'percentage' => $netSales > 0 
                        ? round(($summary['gopay_amount'] / $netSales) * 100, 2) 
                        : 0,
                ],
            ],
        ];
    }
    
    /**
     * Get period type based on days
     */
    protected function getPeriodType(int $days): string
    {
        if ($days == 1) return 'daily';
        if ($days <= 7) return 'weekly';
        if ($days <= 31) return 'monthly';
        if ($days <= 365) return 'yearly';
        return 'custom';
    }
    
    /**
     * Get growth status
     */
    protected function getGrowthStatus(float $percentage): string
    {
        if ($percentage >= 10) return 'excellent';
        if ($percentage >= 5) return 'good';
        if ($percentage >= 0) return 'stable';
        if ($percentage >= -5) return 'warning';
        return 'danger';
    }
    
    /**
     * Get sales trend (for charts)
     * Group by: hourly, daily, weekly, monthly
     */
    public function getSalesTrend(int $tenantId, string $startDate, string $endDate, string $groupBy = 'daily'): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        // Determine grouping format
        $groupFormat = match($groupBy) {
            'hourly' => '%Y-%m-%d %H:00:00',
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u', // Year-Week
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as period"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as avg_sales')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        
        return $orders->map(function($item) use ($groupBy) {
            $label = $this->formatPeriodLabel($item->period, $groupBy);
            
            return [
                'period' => $item->period,
                'label' => $label,
                'total_orders' => (int) $item->total_orders,
                'total_sales' => (float) $item->total_sales,
                'average_sales' => (float) $item->avg_sales,
            ];
        })->toArray();
    }
    
    /**
     * Format period label for display
     */
    protected function formatPeriodLabel(string $period, string $groupBy): string
    {
        return match($groupBy) {
            'hourly' => Carbon::parse($period)->format('H:00'),
            'daily' => Carbon::parse($period)->format('d M'),
            'weekly' => 'Week ' . explode('-', $period)[1],
            'monthly' => Carbon::parse($period . '-01')->format('M Y'),
            default => $period,
        };
    }
    
    /**
     * Get category performance
     */
    public function getCategoryPerformance(int $tenantId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $categories = OrderItem::select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_sales')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sales')
            ->get();
        
        $totalSales = $categories->sum('total_sales');
        
        return $categories->map(function($item) use ($totalSales) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'total_orders' => (int) $item->total_orders,
                'total_quantity' => (int) $item->total_quantity,
                'total_sales' => (float) $item->total_sales,
                'percentage' => $totalSales > 0 ? round(($item->total_sales / $totalSales) * 100, 2) : 0,
            ];
        })->toArray();
    }
    
    /**
     * Get hourly breakdown (for heatmap)
     */
    public function getHourlyBreakdown(int $tenantId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $hourly = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as avg_sales')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        // Fill all 24 hours
        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $found = $hourly->firstWhere('hour', $h);
            $result[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'total_orders' => $found ? (int) $found->total_orders : 0,
                'total_sales' => $found ? (float) $found->total_sales : 0,
                'average_sales' => $found ? (float) $found->avg_sales : 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Get payment method trends (for line/bar chart)
     */
    public function getPaymentTrends(int $tenantId, string $startDate, string $endDate, string $groupBy = 'daily'): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $groupFormat = match($groupBy) {
            'hourly' => '%Y-%m-%d %H:00:00',
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        $trends = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as period"),
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as amount')
            )
            ->groupBy('period', 'payment_method')
            ->orderBy('period')
            ->get();
        
        // Organize by period
        $organized = [];
        foreach ($trends as $item) {
            $label = $this->formatPeriodLabel($item->period, $groupBy);
            
            if (!isset($organized[$item->period])) {
                $organized[$item->period] = [
                    'period' => $item->period,
                    'label' => $label,
                    'cash' => ['count' => 0, 'amount' => 0],
                    'qris' => ['count' => 0, 'amount' => 0],
                    'gopay' => ['count' => 0, 'amount' => 0],
                ];
            }
            
            $organized[$item->period][$item->payment_method] = [
                'count' => (int) $item->count,
                'amount' => (float) $item->amount,
            ];
        }
        
        return array_values($organized);
    }
}
