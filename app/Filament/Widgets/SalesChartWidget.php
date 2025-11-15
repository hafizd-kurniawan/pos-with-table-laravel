<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Sales Trend (Last 7 Days)';
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 6,
    ];

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $service = new DashboardService();
        $data = $service->getSalesTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Daily Sales',
                    'data' => $data['sales'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => '
                            function(value) { 
                                return "Rp " + value.toLocaleString("id-ID"); 
                            }
                        ',
                    ],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $service = new DashboardService();
        $data = $service->getSalesTrend();

        return 'Rata-rata: Rp ' . number_format($data['average'], 0, ',', '.') . '/hari • Tertinggi: Rp ' . number_format($data['best_day'], 0, ',', '.');
    }
}
