<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $service = new DashboardService();
        $summary = $service->getSalesSummary();
        $inventory = $service->getInventoryStats();

        $change = $summary['change_percentage'];
        $trendIcon = $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = $change >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Total Sales', 'Rp ' . number_format($summary['total_sales'], 0, ',', '.'))
                ->description(abs($change) . '% vs previous period')
                ->descriptionIcon($trendIcon)
                ->color($trendColor)
                ->chart($this->getTrendChart($service)),

            Stat::make('Total Orders', number_format($summary['total_orders']))
                ->description('Transactions in period')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make('Avg Order Value', 'Rp ' . number_format($summary['avg_order'], 0, ',', '.'))
                ->description('Per transaction')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
                
            Stat::make('Inventory Value', 'Rp ' . number_format($inventory['total_value'], 0, ',', '.'))
                ->description($inventory['total_items'] . ' items tracked')
                ->icon('heroicon-o-archive-box')
                ->color('info'),
        ];
    }

    private function getTrendChart(DashboardService $service): array
    {
        // Simple trend chart for the sparkline
        $trend = $service->getSalesTrend();
        return $trend['sales'] ?? [];
    }
}
