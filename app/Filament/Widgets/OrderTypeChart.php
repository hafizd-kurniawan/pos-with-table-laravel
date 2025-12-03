<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class OrderTypeChart extends ChartWidget
{
    protected static ?string $heading = '🍽️ Order Types';
    protected static ?int $sort = 7;
    
    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $service = new DashboardService();
        $data = $service->getSalesByOrderType();

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => array_column($data, 'total'),
                    'backgroundColor' => array_column($data, 'color'),
                ],
            ],
            'labels' => array_column($data, 'type'),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
