<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryValueWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
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

        // Determine health display
        $healthLabels = [
            'good' => 'Healthy',
            'warning' => 'Warning',
            'critical' => 'Critical',
        ];

        $healthColors = [
            'good' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
        ];

        $healthIcons = [
            'good' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'critical' => 'heroicon-o-exclamation-circle',
        ];

        $health = $data['health_status'];

        return [
            Stat::make('📊 Inventory', 'Rp ' . number_format($data['total_value'], 0, ',', '.'))
                ->description(
                    $data['total_items'] . ' items • ' . $healthLabels[$health]
                )
                ->descriptionIcon($healthIcons[$health])
                ->color($healthColors[$health]),
        ];
    }
}
