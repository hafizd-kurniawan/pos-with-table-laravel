<?php

namespace App\Filament\Widgets;

use App\Models\Ingredient;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class LowStockAlertsWidget extends Widget
{
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = [
        'md' => 12,
        'xl' => 4,
    ];

    protected static string $view = 'filament.widgets.low-stock-alerts-widget';

    protected static ?string $pollingInterval = '120s';

    public function getAlerts(): array
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        
        return Ingredient::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('current_stock', '<=', DB::raw('min_stock'))
                    ->orWhere('current_stock', '<=', 0);
            })
            ->selectRaw('
                id,
                name,
                current_stock,
                min_stock,
                unit,
                CASE 
                    WHEN current_stock <= 0 THEN "critical"
                    WHEN current_stock <= min_stock THEN "warning"
                END as alert_level,
                CASE
                    WHEN current_stock <= 0 THEN 0
                    ELSE ROUND((current_stock / min_stock) * 100, 0)
                END as percentage
            ')
            ->orderByRaw('CASE WHEN current_stock <= 0 THEN 0 ELSE 1 END')
            ->orderBy('percentage', 'asc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        
        $stats = Ingredient::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->selectRaw('
                SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN current_stock <= min_stock AND current_stock > 0 THEN 1 ELSE 0 END) as low_stock
            ')
            ->first();

        return [
            'out_of_stock' => $stats->out_of_stock ?? 0,
            'low_stock' => $stats->low_stock ?? 0,
            'total' => ($stats->out_of_stock ?? 0) + ($stats->low_stock ?? 0),
        ];
    }
}
