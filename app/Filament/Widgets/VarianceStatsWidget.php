<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VarianceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $items = \App\Models\StockOpnameItem::query()
            ->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
            ->join('ingredients', 'stock_opname_items.ingredient_id', '=', 'ingredients.id')
            ->where('stock_opnames.tenant_id', $tenantId)
            ->where('stock_opnames.status', 'completed')
            ->whereBetween('stock_opnames.completed_at', [$startOfMonth, $endOfMonth])
            ->select(
                'stock_opname_items.difference',
                'ingredients.cost_per_unit'
            )
            ->get();

        $totalLoss = $items->where('difference', '<', 0)->sum(fn($item) => abs($item->difference) * $item->cost_per_unit);
        $totalGain = $items->where('difference', '>', 0)->sum(fn($item) => $item->difference * $item->cost_per_unit);
        $netVariance = $totalGain - $totalLoss;

        return [
            Stat::make('Total Variance Loss (Shortage)', \App\Helpers\FormatHelper::formatCurrency($totalLoss))
                ->description('Value of missing stock this month')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            
            Stat::make('Total Variance Gain (Overage)', \App\Helpers\FormatHelper::formatCurrency($totalGain))
                ->description('Value of extra stock this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Net Variance Value', \App\Helpers\FormatHelper::formatCurrency($netVariance))
                ->description('Net financial impact')
                ->color($netVariance >= 0 ? 'success' : 'danger'),
        ];
    }
}
