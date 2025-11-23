<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Today\'s Sales by Hour';
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = [
        'md' => 12,
        'xl' => 8,
    ];

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        
        // Get hourly sales for today
        $hourlySales = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // Prepare data for all 24 hours (only show operating hours)
        $salesData = [];
        $ordersData = [];
        $labels = [];
        
        // Operating hours: 8 AM to 10 PM (22:00)
        $startHour = 8;
        $endHour = 22;
        
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $data = $hourlySales->get($hour);
            $salesData[] = $data ? (float) $data->total : 0;
            $ordersData[] = $data ? (int) $data->orders : 0;
            $labels[] = sprintf('%02d:00', $hour);
        }

        $totalSales = array_sum($salesData);
        $totalOrders = array_sum($ordersData);
        $peakHour = $salesData ? array_search(max($salesData), $salesData) + $startHour : 0;

        return [
            'datasets' => [
                [
                    'label' => 'Sales (Rp)',
                    'data' => $salesData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Orders',
                    'data' => $ordersData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
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
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Sales (Rp)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Orders',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        
        $peakData = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['paid', 'cooking', 'complete'])
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('hour')
            ->orderBy('total', 'desc')
            ->first();

        if ($peakData) {
            $peakHour = sprintf('%02d:00', $peakData->hour);
            $peakSales = number_format($peakData->total, 0, ',', '.');
            return "🔥 Peak: {$peakHour} (Rp {$peakSales}) • Real-time updates every minute";
        }

        return "Real-time sales tracking • Updates every minute";
    }
}
