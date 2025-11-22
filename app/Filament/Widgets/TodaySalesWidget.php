<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaySalesWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $service = new DashboardService();
        $data = $service->getTodaySales();
        $trend = $service->getSalesTrend();

        $change = $data['change_percentage'];
        $salesColor = $change >= 0 ? 'success' : 'danger';
        $salesIcon = $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return [
            // Sales Today
            Stat::make('💰 Sales Today', 'Rp ' . number_format($data['total_sales'], 0, ',', '.'))
                ->description(
                    ($change >= 0 ? '↗️ +' : '↘️ ') . 
                    number_format(abs($change), 1) . '% vs yesterday'
                )
                ->descriptionIcon($salesIcon)
                ->chart($trend['sales'])
                ->color($salesColor),
            
            // Total Orders Today
            Stat::make('🧾 Orders Today', number_format($data['total_orders'], 0))
                ->description('Total transactions')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
            
            // Average Order Value
            Stat::make('📊 Avg Order Value', 'Rp ' . number_format($data['avg_order'], 0, ',', '.'))
                ->description('Per transaction')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),
            
            // Yesterday Sales (for comparison)
            Stat::make('📅 Yesterday', 'Rp ' . number_format($data['yesterday_sales'], 0, ',', '.'))
                ->description('Previous day sales')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
