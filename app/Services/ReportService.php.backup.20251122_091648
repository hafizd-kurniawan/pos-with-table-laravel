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
            ->where('summary_date', $dateObj->format('Y-m-d'))
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
        $summary['summary_date'] = $dateObj->format('Y-m-d');
        
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
        
        // Payment method breakdown (case-insensitive)
        $cashOrders = $orders->filter(fn($o) => strtolower($o->payment_method) === 'cash');
        $qrisOrders = $orders->filter(fn($o) => strtolower($o->payment_method) === 'qris');
        
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
            ->where('summary_date', $dateObj->format('Y-m-d'))
            ->first();
        
        if ($cached) {
            $formatted = $this->formatDailySummary($cached);
            
            // Add enhancements (calculated on-the-fly)
            $formatted['comparison'] = $this->getYesterdayComparison($tenantId, $dateObj);
            $formatted['peak_hours'] = $this->getPeakHours($tenantId, $dateObj);
            $formatted['customer_insights'] = $this->getCustomerInsights($tenantId, $dateObj);
            $formatted['weekly_trend'] = $this->getWeeklyTrend($tenantId, $dateObj);
            $formatted['stock_alerts'] = $this->getStockAlerts($tenantId, $dateObj);
            $formatted['staff_performance'] = $this->getStaffPerformance($tenantId, $dateObj);
            $formatted['profit_analysis'] = $this->getProfitAnalysis($tenantId, $dateObj);
            
            return $formatted;
        }
        
        // Calculate on-the-fly
        $summary = $this->calculateDailySummary($tenantId, $dateObj);
        $summary['summary_date'] = $dateObj->format('Y-m-d');
        
        $formatted = $this->formatSummaryArray($summary, $dateObj);
        
        // Add enhancements
        $formatted['comparison'] = $this->getYesterdayComparison($tenantId, $dateObj);
        $formatted['peak_hours'] = $this->getPeakHours($tenantId, $dateObj);
        $formatted['customer_insights'] = $this->getCustomerInsights($tenantId, $dateObj);
        $formatted['weekly_trend'] = $this->getWeeklyTrend($tenantId, $dateObj);
        $formatted['stock_alerts'] = $this->getStockAlerts($tenantId, $dateObj);
        $formatted['staff_performance'] = $this->getStaffPerformance($tenantId, $dateObj);
        $formatted['profit_analysis'] = $this->getProfitAnalysis($tenantId, $dateObj);
        
        return $formatted;
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
            'date' => $summary->summary_date->format('Y-m-d'),
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
                    'amount' => (float) ($summary['cash_amount'] ?? 0),
                    'count' => $summary['cash_count'] ?? 0,
                    'percentage' => $netSales > 0 
                        ? round((($summary['cash_amount'] ?? 0) / $netSales) * 100, 2) 
                        : 0,
                ],
                [
                    'method' => 'qris',
                    'amount' => (float) ($summary['qris_amount'] ?? 0),
                    'count' => $summary['qris_count'] ?? 0,
                    'percentage' => $netSales > 0 
                        ? round((($summary['qris_amount'] ?? 0) / $netSales) * 100, 2) 
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
    
    /**
     * Get yesterday comparison
     */
    public function getYesterdayComparison(int $tenantId, Carbon $date): array
    {
        $yesterday = $date->copy()->subDay();
        
        // Get today's summary
        $todaySummary = $this->calculateDailySummary($tenantId, $date);
        
        // Get yesterday's summary
        $yesterdaySummary = $this->calculateDailySummary($tenantId, $yesterday);
        
        // Calculate changes
        $revenueChange = $yesterdaySummary['net_sales'] > 0 
            ? (($todaySummary['net_sales'] - $yesterdaySummary['net_sales']) / $yesterdaySummary['net_sales']) * 100 
            : 0;
            
        $ordersChange = $yesterdaySummary['total_orders'] > 0 
            ? (($todaySummary['total_orders'] - $yesterdaySummary['total_orders']) / $yesterdaySummary['total_orders']) * 100 
            : 0;
            
        $avgChange = $yesterdaySummary['total_orders'] > 0 && $todaySummary['total_orders'] > 0
            ? ((($todaySummary['net_sales'] / $todaySummary['total_orders']) - ($yesterdaySummary['net_sales'] / $yesterdaySummary['total_orders'])) / ($yesterdaySummary['net_sales'] / $yesterdaySummary['total_orders'])) * 100
            : 0;
        
        return [
            'yesterday' => [
                'date' => $yesterday->format('Y-m-d'),
                'total_orders' => $yesterdaySummary['total_orders'],
                'net_sales' => $yesterdaySummary['net_sales'],
                'average_transaction' => $yesterdaySummary['total_orders'] > 0 
                    ? $yesterdaySummary['net_sales'] / $yesterdaySummary['total_orders'] 
                    : 0,
            ],
            'changes' => [
                'revenue' => [
                    'amount' => $todaySummary['net_sales'] - $yesterdaySummary['net_sales'],
                    'percentage' => round($revenueChange, 1),
                    'trend' => $revenueChange >= 0 ? 'up' : 'down',
                ],
                'orders' => [
                    'amount' => $todaySummary['total_orders'] - $yesterdaySummary['total_orders'],
                    'percentage' => round($ordersChange, 1),
                    'trend' => $ordersChange >= 0 ? 'up' : 'down',
                ],
                'average' => [
                    'percentage' => round($avgChange, 1),
                    'trend' => $avgChange >= 0 ? 'up' : 'down',
                ],
            ],
        ];
    }
    
    /**
     * Get peak hours analysis
     */
    public function getPeakHours(int $tenantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        // Get hourly breakdown
        $hourly = DB::table('orders')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total_amount) as revenue')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();
        
        if ($hourly->isEmpty()) {
            return [
                'busiest' => null,
                'slowest' => null,
                'breakdown' => [],
            ];
        }
        
        $sorted = $hourly->sortByDesc('count')->values();
        
        return [
            'busiest' => [
                'hour' => sprintf('%02d:00', $sorted->first()->hour),
                'orders' => $sorted->first()->count,
                'revenue' => (float) $sorted->first()->revenue,
            ],
            'slowest' => [
                'hour' => sprintf('%02d:00', $sorted->last()->hour),
                'orders' => $sorted->last()->count,
                'revenue' => (float) $sorted->last()->revenue,
            ],
            'breakdown' => $hourly->map(function($item) {
                return [
                    'hour' => sprintf('%02d:00', $item->hour),
                    'orders' => (int) $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            })->toArray(),
        ];
    }
    
    /**
     * Get customer insights
     */
    public function getCustomerInsights(int $tenantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        // Get all orders for the day
        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->get();
        
        $totalOrders = $orders->count();
        
        // Unique customers
        $uniqueCustomers = $orders->whereNotNull('customer_name')->unique('customer_name')->count();
        if ($uniqueCustomers == 0) {
            $uniqueCustomers = $totalOrders; // Assume each order is unique customer if no names
        }
        
        // Get previous week data for repeat calculation (simplified)
        $weekAgo = $date->copy()->subDays(7);
        $previousCustomers = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$weekAgo, $startOfDay])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->whereNotNull('customer_name')
            ->pluck('customer_name')
            ->unique();
        
        $todayCustomers = $orders->whereNotNull('customer_name')->pluck('customer_name')->unique();
        $repeatCustomers = $todayCustomers->intersect($previousCustomers)->count();
        $newCustomers = $uniqueCustomers - $repeatCustomers;
        
        // Calculate average items per order
        $totalItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->sum('order_items.quantity');
        
        $avgItemsPerOrder = $totalOrders > 0 ? $totalItems / $totalOrders : 0;
        
        return [
            'total_orders' => $totalOrders,
            'unique_customers' => $uniqueCustomers,
            'repeat_customers' => $repeatCustomers,
            'new_customers' => max(0, $newCustomers),
            'repeat_percentage' => $uniqueCustomers > 0 ? round(($repeatCustomers / $uniqueCustomers) * 100, 1) : 0,
            'new_percentage' => $uniqueCustomers > 0 ? round(($newCustomers / $uniqueCustomers) * 100, 1) : 0,
            'avg_items_per_order' => round($avgItemsPerOrder, 1),
            'total_items' => (int) $totalItems,
        ];
    }
    
    /**
     * Get weekly trend (last 7 days)
     */
    public function getWeeklyTrend(int $tenantId, Carbon $endDate): array
    {
        $days = [];
        $dates = [];
        
        // Get last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = $endDate->copy()->subDays($i);
            $dates[] = $date;
            
            $daySummary = $this->calculateDailySummary($tenantId, $date);
            
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'day_short' => $date->format('D'),
                'revenue' => $daySummary['net_sales'],
                'orders' => $daySummary['total_orders'],
                'items' => $daySummary['total_items'],
            ];
        }
        
        // Calculate totals
        $totalRevenue = array_sum(array_column($days, 'revenue'));
        $totalOrders = array_sum(array_column($days, 'orders'));
        
        // Find best and worst days
        $sortedByRevenue = collect($days)->sortByDesc('revenue')->values();
        $bestDay = $sortedByRevenue->first();
        $worstDay = $sortedByRevenue->last();
        
        // Calculate week-over-week growth
        $previousWeekStart = $endDate->copy()->subDays(13);
        $previousWeekEnd = $endDate->copy()->subDays(7);
        
        $previousWeekOrders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$previousWeekStart->startOfDay(), $previousWeekEnd->endOfDay()])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->sum('total_amount');
        
        $weekOverWeekGrowth = $previousWeekOrders > 0 
            ? (($totalRevenue - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;
        
        return [
            'days' => $days,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'average_per_day' => $totalOrders > 0 ? $totalRevenue / 7 : 0,
                'best_day' => $bestDay,
                'worst_day' => $worstDay,
            ],
            'growth' => [
                'previous_week_revenue' => $previousWeekOrders,
                'current_week_revenue' => $totalRevenue,
                'percentage' => round($weekOverWeekGrowth, 1),
                'trend' => $weekOverWeekGrowth >= 0 ? 'up' : 'down',
            ],
        ];
    }
    
    /**
     * Get stock alerts (low stock items)
     */
    public function getStockAlerts(int $tenantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        // Get today's sales to calculate velocity
        $todaySales = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as sold_today'))
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
        
        // Get products with stock info
        $products = DB::table('products')
            ->select('id', 'name', 'stock', 'category_id')
            ->where('tenant_id', $tenantId)
            ->where('stock', '>', 0)
            ->get();
        
        $alerts = [];
        
        foreach ($products as $product) {
            $soldToday = $todaySales[$product->id]->sold_today ?? 0;
            $stock = $product->stock;
            
            // Determine alert level
            $alertLevel = null;
            if ($stock < 10 && $soldToday > 0) {
                $alertLevel = 'critical';
            } elseif ($stock < 20 && $soldToday > 5) {
                $alertLevel = 'warning';
            } elseif ($stock < 30 && $soldToday > 10) {
                $alertLevel = 'watch';
            }
            
            if ($alertLevel) {
                $daysUntilStockout = $soldToday > 0 ? floor($stock / $soldToday) : 999;
                
                $alerts[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_stock' => $stock,
                    'sold_today' => $soldToday,
                    'days_until_stockout' => $daysUntilStockout,
                    'alert_level' => $alertLevel,
                    'recommendation' => $daysUntilStockout <= 2 ? 'Reorder NOW!' : 'Monitor closely',
                ];
            }
        }
        
        // Sort by alert level (critical first)
        $sorted = collect($alerts)->sortBy(function($item) {
            $priority = ['critical' => 1, 'warning' => 2, 'watch' => 3];
            return $priority[$item['alert_level']];
        })->values()->toArray();
        
        return [
            'alerts' => $sorted,
            'summary' => [
                'critical_count' => collect($sorted)->where('alert_level', 'critical')->count(),
                'warning_count' => collect($sorted)->where('alert_level', 'warning')->count(),
                'watch_count' => collect($sorted)->where('alert_level', 'watch')->count(),
            ],
        ];
    }
    
    /**
     * Get staff performance
     */
    public function getStaffPerformance(int $tenantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        // Get performance per cashier
        $performance = DB::table('orders')
            ->select(
                'cashier_id',
                'users.name as user_name',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('AVG(total_amount) as avg_transaction')
            )
            ->join('users', 'orders.cashier_id', '=', 'users.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
            ->whereIn('orders.status', ['paid', 'cooking', 'complete'])
            ->whereNotNull('orders.cashier_id')
            ->groupBy('cashier_id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get();
        
        if ($performance->isEmpty()) {
            return [
                'staff' => [],
                'summary' => [
                    'total_staff' => 0,
                    'avg_orders_per_staff' => 0,
                    'avg_revenue_per_staff' => 0,
                ],
            ];
        }
        
        $totalOrders = $performance->sum('total_orders');
        $totalRevenue = $performance->sum('total_revenue');
        $staffCount = $performance->count();
        
        $avgOrdersPerStaff = $totalOrders / $staffCount;
        $avgRevenuePerStaff = $totalRevenue / $staffCount;
        
        $staff = $performance->map(function($item, $index) use ($avgOrdersPerStaff, $avgRevenuePerStaff) {
            $ordersVsAvg = $avgOrdersPerStaff > 0 
                ? (($item->total_orders - $avgOrdersPerStaff) / $avgOrdersPerStaff) * 100 
                : 0;
                
            return [
                'user_id' => $item->cashier_id,
                'user_name' => $item->user_name,
                'total_orders' => (int) $item->total_orders,
                'total_revenue' => (float) $item->total_revenue,
                'avg_transaction' => (float) $item->avg_transaction,
                'rank' => $index + 1,
                'performance_vs_avg' => round($ordersVsAvg, 1),
                'badge' => $index === 0 ? 'top_performer' : ($ordersVsAvg > 0 ? 'above_average' : 'below_average'),
            ];
        })->toArray();
        
        return [
            'staff' => $staff,
            'summary' => [
                'total_staff' => $staffCount,
                'avg_orders_per_staff' => round($avgOrdersPerStaff, 1),
                'avg_revenue_per_staff' => round($avgRevenuePerStaff, 0),
            ],
        ];
    }
    
    /**
     * Get profit analysis
     */
    public function getProfitAnalysis(int $tenantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        // Get orders for the day
        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->with('orderItems.product')
            ->get();
        
        if ($orders->isEmpty()) {
            return [
                'summary' => [
                    'gross_revenue' => 0,
                    'total_cogs' => 0,
                    'total_discount' => 0,
                    'net_profit' => 0,
                    'profit_margin' => 0,
                    'target_margin' => 35,
                    'margin_difference' => -35,
                ],
                'products' => [],
                'recommendations' => [],
            ];
        }
        
        $grossRevenue = $orders->sum('subtotal'); // Before tax & service
        $totalDiscount = $orders->sum('discount_amount');
        $totalCogs = 0;
        $productProfits = [];
        
        // Calculate COGS and product-level profitability
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                if (!$product) continue;
                
                $productCost = $product->cost * $item->quantity;
                $productRevenue = $item->price * $item->quantity;
                $productProfit = $productRevenue - $productCost;
                $productMargin = $productRevenue > 0 ? ($productProfit / $productRevenue) * 100 : 0;
                
                $totalCogs += $productCost;
                
                // Aggregate by product
                if (!isset($productProfits[$product->id])) {
                    $productProfits[$product->id] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity_sold' => 0,
                        'revenue' => 0,
                        'cogs' => 0,
                        'profit' => 0,
                        'margin' => 0,
                        'target_margin' => $product->profit_margin_target,
                    ];
                }
                
                $productProfits[$product->id]['quantity_sold'] += $item->quantity;
                $productProfits[$product->id]['revenue'] += $productRevenue;
                $productProfits[$product->id]['cogs'] += $productCost;
                $productProfits[$product->id]['profit'] += $productProfit;
            }
        }
        
        // Calculate margins for each product
        foreach ($productProfits as $id => $data) {
            $productProfits[$id]['margin'] = $data['revenue'] > 0 
                ? round(($data['profit'] / $data['revenue']) * 100, 1)
                : 0;
        }
        
        // Sort by profit descending
        usort($productProfits, function($a, $b) {
            return $b['profit'] <=> $a['profit'];
        });
        
        // Calculate overall profit
        $netProfit = $grossRevenue - $totalCogs - $totalDiscount;
        $profitMargin = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0;
        $targetMargin = 35; // Default target
        $marginDifference = $profitMargin - $targetMargin;
        
        // Generate recommendations
        $recommendations = [];
        
        if ($profitMargin < $targetMargin) {
            $recommendations[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'message' => "Profit margin ({$profitMargin}%) below target ({$targetMargin}%)",
                'action' => 'Consider increasing prices or reducing costs',
            ];
        } else {
            $recommendations[] = [
                'type' => 'success',
                'icon' => '✅',
                'message' => "Profit margin ({$profitMargin}%) meets target!",
                'action' => 'Keep up the good work',
            ];
        }
        
        // Find low-margin products
        $lowMarginProducts = array_filter($productProfits, function($p) {
            return $p['margin'] < 20;
        });
        
        if (!empty($lowMarginProducts)) {
            $names = array_slice(array_column($lowMarginProducts, 'product_name'), 0, 3);
            $recommendations[] = [
                'type' => 'warning',
                'icon' => '📉',
                'message' => 'Low margin products: ' . implode(', ', $names),
                'action' => 'Review pricing or reduce costs',
            ];
        }
        
        // Find high-margin products to promote
        $highMarginProducts = array_filter($productProfits, function($p) {
            return $p['margin'] > 50;
        });
        
        if (!empty($highMarginProducts)) {
            $names = array_slice(array_column($highMarginProducts, 'product_name'), 0, 3);
            $recommendations[] = [
                'type' => 'success',
                'icon' => '🎯',
                'message' => 'High margin products: ' . implode(', ', $names),
                'action' => 'Promote these items more!',
            ];
        }
        
        return [
            'summary' => [
                'gross_revenue' => $grossRevenue,
                'total_cogs' => $totalCogs,
                'total_discount' => $totalDiscount,
                'net_profit' => $netProfit,
                'profit_margin' => round($profitMargin, 1),
                'target_margin' => $targetMargin,
                'margin_difference' => round($marginDifference, 1),
            ],
            'products' => $productProfits,
            'recommendations' => $recommendations,
        ];
    }
}
