<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;
    protected static string $view = 'filament.pages.dashboard-styles';

    public function getWidgets(): array
    {
        return [
            // Row 1: Stats Overview (4 cards)
            \App\Filament\Widgets\TodaySalesWidget::class,
            \App\Filament\Widgets\TotalOrdersWidget::class,
            \App\Filament\Widgets\InventoryValueWidget::class,
            \App\Filament\Widgets\LowStockAlertsWidget::class,
            
            // Row 2: Charts (2 columns)
            \App\Filament\Widgets\SalesChartWidget::class,
            \App\Filament\Widgets\InventoryHealthWidget::class,
            
            // Row 3: Tables (2 columns)
            \App\Filament\Widgets\CriticalAlertsWidget::class,
            \App\Filament\Widgets\TopProductsWidget::class,
            
            // Row 4: Recent Orders (full width)
            \App\Filament\Widgets\RecentOrdersWidget::class,
            
            // Row 5: Activity & Actions (2 columns)
            \App\Filament\Widgets\StockMovementsWidget::class,
            \App\Filament\Widgets\PendingActionsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 4,
            'lg' => 6,
            'xl' => 12,
            '2xl' => 12,
        ];
    }

    public function getHeading(): string
    {
        $tenant = auth()->user()->tenant ?? null;
        return ($tenant ? $tenant->business_name . ' - ' : '') . 'Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Last updated: ' . now()->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $service = new \App\Services\DashboardService();
                    $service->clearCache();
                    
                    $this->dispatch('$refresh');
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Dashboard refreshed')
                        ->success()
                        ->send();
                }),
        ];
    }
}
