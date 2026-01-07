<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SalesTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Trend (This Month)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $data = Order::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates with 0
        $labels = [];
        $values = [];
        $current = $startOfMonth->copy();

        while ($current <= $endOfMonth && $current <= now()) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('d M');
            
            $record = $data->firstWhere('date', $dateStr);
            $values[] = $record ? $record->total : 0;
            
            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daily Sales (Rp)',
                    'data' => $values,
                    'borderColor' => '#10b981', // Emerald 500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
