<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\Widget;

class TopProductsWidget extends Widget
{
    protected static ?int $sort = 8;
    
    protected int | string | array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected static string $view = 'filament.widgets.top-products-widget';

    protected static ?string $pollingInterval = '120s';

    public function getProducts(): array
    {
        $service = new DashboardService();
        return $service->getTopProducts(7, 5);
    }
}
