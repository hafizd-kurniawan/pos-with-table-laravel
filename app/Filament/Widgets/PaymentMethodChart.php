<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class PaymentMethodChart extends ChartWidget
{
    protected static ?string $heading = '💳 Payment Methods';
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $service = new DashboardService();
        $data = $service->getSalesByPaymentMethod();

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => array_column($data, 'total'),
                    'backgroundColor' => [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'
                    ],
                ],
            ],
            'labels' => array_column($data, 'method'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
