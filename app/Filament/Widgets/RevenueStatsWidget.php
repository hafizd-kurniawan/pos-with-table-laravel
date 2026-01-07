<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1; // Top priority

    protected function getStats(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $orders = Order::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalRevenue = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Previous Month for comparison (Trend)
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();
        
        $lastMonthRevenue = Order::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_amount');

        $revenueTrend = $lastMonthRevenue > 0 
            ? (($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 100;

        return [
            Stat::make('Total Revenue (This Month)', \App\Helpers\FormatHelper::formatCurrency($totalRevenue))
                ->description($revenueTrend >= 0 ? '+' . number_format($revenueTrend, 1) . '% vs last month' : number_format($revenueTrend, 1) . '% vs last month')
                ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueTrend >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Dummy chart for visual appeal

            Stat::make('Total Orders', number_format($totalOrders))
                ->description('Transactions completed')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make('Average Order Value', \App\Helpers\FormatHelper::formatCurrency($avgOrderValue))
                ->description('Revenue per order')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
