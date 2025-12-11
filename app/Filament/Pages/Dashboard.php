<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    public static function getNavigationLabel(): string
    {
        return __('report.dashboard.title');
    }
    protected static ?int $navigationSort = -10;
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
        return ($tenant ? $tenant->business_name . ' - ' : '') . __('report.dashboard.title');
    }

    public function getSubheading(): ?string
    {
        return __('report.dashboard.last_updated', ['time' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i')]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label(__('report.dashboard.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $service = new \App\Services\DashboardService();
                    $service->clearCache();
                    
                    $this->dispatch('$refresh');
                    
                    \Filament\Notifications\Notification::make()
                        ->title(__('report.dashboard.refreshed'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
