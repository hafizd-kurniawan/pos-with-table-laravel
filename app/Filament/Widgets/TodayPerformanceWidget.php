<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\Widget;

class TodayPerformanceWidget extends Widget
{
    protected static string $view = 'filament.widgets.today-performance-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    public function getViewData(): array
    {
        $dashboardService = new DashboardService();
        return [
            'data' => $dashboardService->getTodaySales(),
        ];
    }
}
