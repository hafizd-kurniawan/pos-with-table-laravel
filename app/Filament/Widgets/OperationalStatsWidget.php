<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Ingredient;
use App\Models\CashierSession;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;

class OperationalStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $tenantId = Auth::user()->tenant_id;

        // Low Stock
        $lowStockCount = Ingredient::where('tenant_id', $tenantId)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        // Open Shifts
        $openShiftsCount = CashierSession::where('tenant_id', $tenantId)
            ->whereNull('ended_at')
            ->count();

        // Pending Leaves
        $pendingLeavesCount = Leave::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Low Stock Items', $lowStockCount)
                ->description('Items below minimum level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Active Cashier Shifts', $openShiftsCount)
                ->description('Currently open sessions')
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Pending Leave Requests', $pendingLeavesCount)
                ->description('Requires approval')
                ->icon('heroicon-o-document-check')
                ->color($pendingLeavesCount > 0 ? 'warning' : 'success'),
        ];
    }
}
