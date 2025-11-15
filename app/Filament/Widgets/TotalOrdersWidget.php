<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
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

        $avgOrder = $data['avg_order'];

        return [
            Stat::make('📦 Orders', number_format($data['total_orders']))
                ->description('Avg: Rp ' . number_format($avgOrder, 0, ',', '.'))
                ->color('primary')
                ->chart(array_fill(0, 7, max(1, $data['total_orders'] / 7))),
        ];
    }
}
