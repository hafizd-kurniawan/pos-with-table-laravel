<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaySalesWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = [
        'md' => 6,    // Medium: 50% (2 per row)
        'lg' => 4,    // Large: 33% (3 per row) - Laptop 1366x768
        'xl' => 3,    // XL: 25% (4 per row) - Laptop 1600x900+
        '2xl' => 3,   // 2XL: 25% (4 per row)
    ];

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $service = new DashboardService();
        $data = $service->getTodaySales();
        $trend = $service->getSalesTrend();

        $change = $data['change_percentage'];
        $color = $change >= 0 ? 'success' : 'danger';
        $icon = $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return [
            Stat::make('💰 Sales Today', 'Rp ' . number_format($data['total_sales'], 0, ',', '.'))
                ->description(
                    ($change >= 0 ? '↗️ +' : '↘️ ') . 
                    number_format(abs($change), 0, ',', '.') . '% vs yesterday'
                )
                ->chart($trend['sales'])
                ->color($color),
        ];
    }
}
