<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = [
        'md' => 6,    // Medium: 50% (2 per row)
        'lg' => 4,    // Large: 33% (3 per row) - Laptop 1366x768
        'xl' => 3,    // XL: 25% (4 per row) - Laptop 1600x900+
        '2xl' => 3,   // 2XL: 25% (4 per row)
    ];

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $service = new DashboardService();
        $data = $service->getInventoryStats();

        $total = $data['total_alerts'];
        $critical = $data['out_of_stock'];
        $warning = $data['low_stock'];

        $color = $critical > 0 ? 'danger' : ($warning > 0 ? 'warning' : 'success');
        $icon = $critical > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-bell-alert';

        return [
            Stat::make('⚠️ Alerts', number_format($total))
                ->description(
                    $critical . ' critical • ' . $warning . ' low'
                )
                ->descriptionIcon($icon)
                ->color($color)
                ->url(route('filament.admin.resources.ingredients.index')),
        ];
    }
}
