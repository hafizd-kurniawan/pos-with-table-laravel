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
            Stat::make(__('report.widgets.stats_overview.total_sales'), \App\Helpers\FormatHelper::formatCurrency($summary['total_sales']))
                ->description(__('report.widgets.stats_overview.vs_previous', ['percentage' => abs($change)]))
                ->descriptionIcon($trendIcon)
                ->color($trendColor)
                ->chart($this->getTrendChart($service)),

            Stat::make(__('report.widgets.stats_overview.total_orders'), \App\Helpers\FormatHelper::formatNumber($summary['total_orders'], 0))
                ->description(__('report.widgets.stats_overview.transactions_period'))
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make(__('report.widgets.stats_overview.avg_order_value'), \App\Helpers\FormatHelper::formatCurrency($summary['avg_order']))
                ->description(__('report.widgets.stats_overview.per_transaction'))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
                
            Stat::make(__('report.widgets.stats_overview.inventory_value'), \App\Helpers\FormatHelper::formatCurrency($inventory['total_value']))
                ->description(__('report.widgets.stats_overview.items_tracked', ['count' => $inventory['total_items']]))
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
