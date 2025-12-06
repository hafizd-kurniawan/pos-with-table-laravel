<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ResourceOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenantId = Auth::user()->tenant_id;

        return [
            Stat::make('Total Products', Product::count())
                ->icon('heroicon-o-cube')
                ->color('success'),
            
            Stat::make('Categories', Category::count())
                ->icon('heroicon-o-tag')
                ->color('primary'),
            
            Stat::make('Tables', Table::count())
                ->icon('heroicon-o-table-cells')
                ->color('warning'),
                
            Stat::make('Staff Members', User::where('tenant_id', $tenantId)->count())
                ->icon('heroicon-o-users')
                ->color('info'),
        ];
    }
}
