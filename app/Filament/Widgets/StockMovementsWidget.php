<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\Widget;

class StockMovementsWidget extends Widget
{
    protected static ?int $sort = 10;
    
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 6,
    ];

    protected static string $view = 'filament.widgets.stock-movements-widget';

    protected static ?string $pollingInterval = '60s';

    public function getMovements(): array
    {
        $service = new DashboardService();
        return $service->getRecentStockMovements(5);
    }
}
