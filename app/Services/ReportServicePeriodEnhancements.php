<?php

// THESE METHODS SHOULD BE ADDED TO ReportService.php BEFORE THE CLOSING BRACE

    /**
     * Get daily trend for period
     */
    protected function getPeriodDailyTrend(int $tenantId, Carbon $start, Carbon $end): array
    {
        $dailyData = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as sales'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');
        
        $labels = [];
        $salesData = [];
        $ordersData = [];
        
        // Fill all dates in range
        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $data = $dailyData->get($dateStr);
            
            $labels[] = $current->format('d M');
            $salesData[] = $data ? (float) $data->sales : 0;
            $ordersData[] = $data ? (int) $data->orders : 0;
            
            $current->addDay();
        }
        
        $bestDayIndex = $salesData ? array_search(max($salesData), $salesData) : 0;
        $worstDayIndex = $salesData ? array_search(min(array_filter($salesData)), $salesData) : 0;
        
        return [
            'labels' => $labels,
            'sales' => $salesData,
            'orders' => $ordersData,
            'average' => count($salesData) > 0 ? array_sum($salesData) / count($salesData) : 0,
            'best_day' => [
                'date' => $labels[$bestDayIndex] ?? null,
                'amount' => $salesData[$bestDayIndex] ?? 0,
            ],
            'worst_day' => [
                'date' => $labels[$worstDayIndex] ?? null,
                'amount' => $salesData[$worstDayIndex] ?? 0,
            ],
        ];
    }
    
    /**
     * Get profit analysis for period
     */
    protected function getPeriodProfitAnalysis(int $tenantId, Carbon $start, Carbon $end): array
    {
        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->with('orderItems.product')
            ->get();
        
        $totalRevenue = 0;
        $totalCogs = 0;
        $productProfits = [];
        
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                if (!$product) continue;
                
                $productCost = ($product->cost ?? 0) * $item->quantity;
                $productRevenue = $item->price * $item->quantity;
                $productProfit = $productRevenue - $productCost;
                
                $totalCogs += $productCost;
                $totalRevenue += $productRevenue;
                
                if (!isset($productProfits[$product->id])) {
                    $productProfits[$product->id] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity_sold' => 0,
                        'revenue' => 0,
                        'cogs' => 0,
                        'profit' => 0,
                    ];
                }
                
                $productProfits[$product->id]['quantity_sold'] += $item->quantity;
                $productProfits[$product->id]['revenue'] += $productRevenue;
                $productProfits[$product->id]['cogs'] += $productCost;
                $productProfits[$product->id]['profit'] += $productProfit;
            }
        }
        
        // Calculate margins and sort by profit
        $products = collect($productProfits)->map(function($item) {
            $margin = $item['revenue'] > 0 ? ($item['profit'] / $item['revenue']) * 100 : 0;
            $item['margin'] = round($margin, 1);
            return $item;
        })->sortByDesc('profit')->values()->take(10)->toArray();
        
        $netProfit = $totalRevenue - $totalCogs;
        $overallMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
        
        // Smart recommendations
        $recommendations = [];
        
        $highMargin = collect($products)->where('margin', '>=', 50)->take(3);
        if ($highMargin->count() > 0) {
            $names = $highMargin->pluck('product_name')->implode(', ');
            $recommendations[] = [
                'type' => 'success',
                'icon' => '🎯',
                'message' => "High margin products: {$names}",
                'action' => 'Promote these items more!',
            ];
        }
        
        $lowMargin = collect($products)->where('margin', '<', 20)->take(3);
        if ($lowMargin->count() > 0) {
            $names = $lowMargin->pluck('product_name')->implode(', ');
            $recommendations[] = [
                'type' => 'warning',
                'icon' => '📉',
                'message' => "Low margin products: {$names}",
                'action' => 'Review pricing or reduce costs',
            ];
        }
        
        return [
            'total_revenue' => (float) $totalRevenue,
            'total_cogs' => (float) $totalCogs,
            'net_profit' => (float) $netProfit,
            'margin_percentage' => round($overallMargin, 2),
            'products' => $products,
            'recommendations' => $recommendations,
        ];
    }
    
    /**
     * Get staff performance for period
     */
    protected function getPeriodStaffPerformance(int $tenantId, Carbon $start, Carbon $end): array
    {
        $staffData = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->whereNotNull('created_by')
            ->select(
                'created_by',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as avg_transaction')
            )
            ->groupBy('created_by')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
        
        $staff = $staffData->map(function($item, $index) {
            $user = \App\Models\User::find($item->created_by);
            
            return [
                'rank' => $index + 1,
                'user_id' => $item->created_by,
                'name' => $user ? $user->name : 'Unknown',
                'total_orders' => (int) $item->total_orders,
                'total_sales' => (float) $item->total_sales,
                'average_transaction' => (float) $item->avg_transaction,
            ];
        })->toArray();
        
        $totalSales = collect($staff)->sum('total_sales');
        
        return [
            'staff' => $staff,
            'total_staff' => count($staff),
            'total_sales' => (float) $totalSales,
        ];
    }
    
    /**
     * Get customer insights for period
     */
    protected function getPeriodCustomerInsights(int $tenantId, Carbon $start, Carbon $end, $orders): array
    {
        $uniqueCustomers = $orders->whereNotNull('customer_name')->unique('customer_name')->count();
        
        if ($uniqueCustomers == 0) {
            $uniqueCustomers = $orders->count(); // Assume each order = 1 customer
        }
        
        $totalSpent = $orders->sum('total_amount');
        $avgSpend = $uniqueCustomers > 0 ? $totalSpent / $uniqueCustomers : 0;
        
        // Top customers
        $topCustomers = $orders->whereNotNull('customer_name')
            ->groupBy('customer_name')
            ->map(function($customerOrders) {
                return [
                    'name' => $customerOrders->first()->customer_name,
                    'total_orders' => $customerOrders->count(),
                    'total_spent' => $customerOrders->sum('total_amount'),
                ];
            })
            ->sortByDesc('total_spent')
            ->take(5)
            ->values()
            ->toArray();
        
        // Previous period comparison
        $days = (int) ($start->diffInDays($end) + 1);
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subDay();
        
        $previousOrders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->get();
        
        $previousCustomers = $previousOrders->whereNotNull('customer_name')->unique('customer_name')->count();
        if ($previousCustomers == 0) {
            $previousCustomers = $previousOrders->count();
        }
        
        $customerGrowth = $previousCustomers > 0 
            ? (($uniqueCustomers - $previousCustomers) / $previousCustomers) * 100 
            : 0;
        
        return [
            'total_customers' => $uniqueCustomers,
            'average_spend' => (float) $avgSpend,
            'top_customers' => $topCustomers,
            'growth' => [
                'previous_period_customers' => $previousCustomers,
                'current_period_customers' => $uniqueCustomers,
                'percentage' => round($customerGrowth, 2),
                'trend' => $customerGrowth >= 0 ? 'up' : 'down',
            ],
        ];
    }
