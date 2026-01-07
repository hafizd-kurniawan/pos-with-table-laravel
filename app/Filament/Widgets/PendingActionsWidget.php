<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\Widget;

class PendingActionsWidget extends Widget
{
    protected static ?int $sort = 11;
    
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 6,
    ];

    protected static string $view = 'filament.widgets.pending-actions-widget';

    protected static ?string $pollingInterval = '120s';

    public function getActions(): array
    {
        $service = new DashboardService();
        return $service->getPendingActions();
    }
}
