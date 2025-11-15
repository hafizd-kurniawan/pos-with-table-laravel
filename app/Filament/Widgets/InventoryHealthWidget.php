<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class InventoryHealthWidget extends ChartWidget
{
    protected static ?string $heading = 'Inventory Health';
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 6,
    ];

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $service = new DashboardService();
        $data = $service->getInventoryStats();

        $healthy = $data['healthy'];
        $lowStock = $data['low_stock'];
        $outOfStock = $data['out_of_stock'];
        $total = $healthy + $lowStock + $outOfStock;

        // Calculate percentages
        $healthyPct = $total > 0 ? round(($healthy / $total) * 100) : 0;
        $lowPct = $total > 0 ? round(($lowStock / $total) * 100) : 0;
        $outPct = $total > 0 ? round(($outOfStock / $total) * 100) : 0;

        return [
            'datasets' => [
                [
                    'label' => 'Inventory Status',
                    'data' => [$healthy, $lowStock, $outOfStock],
                    'backgroundColor' => [
                        'rgb(16, 185, 129)',  // Green - Healthy
                        'rgb(245, 158, 11)',  // Yellow - Low
                        'rgb(220, 38, 38)',   // Red - Out
                    ],
                ],
            ],
            'labels' => [
                "Healthy ({$healthyPct}%)",
                "Low Stock ({$lowPct}%)",
                "Out of Stock ({$outPct}%)",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $service = new DashboardService();
        $data = $service->getInventoryStats();

        $actionNeeded = $data['low_stock'] + $data['out_of_stock'];
        
        if ($actionNeeded > 0) {
            return "⚠️ {$actionNeeded} items need attention";
        }

        return '✅ All items in good stock';
    }
}
