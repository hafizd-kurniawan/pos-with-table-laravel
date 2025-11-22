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
            // Row 1: Key Metrics (Full Width - 4 Cards)
            \App\Filament\Widgets\TodaySalesWidget::class,
            
            // Row 2: Main Charts (2 Columns - 8:4 ratio)
            \App\Filament\Widgets\SalesChartWidget::class,
            \App\Filament\Widgets\LowStockAlertsWidget::class,
            
            // Row 3: Performance Insights (2 Columns - 6:6 ratio)
            \App\Filament\Widgets\TopProductsWidget::class,
            \App\Filament\Widgets\CriticalAlertsWidget::class,
            
            // Row 4: Recent Activity (Full Width)
            \App\Filament\Widgets\RecentOrdersWidget::class,
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
