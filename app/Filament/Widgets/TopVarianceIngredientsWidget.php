<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopVarianceIngredientsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Ingredient::query()
                    ->join('stock_opname_items', 'ingredients.id', '=', 'stock_opname_items.ingredient_id')
                    ->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
                    ->where('stock_opnames.tenant_id', auth()->user()->tenant_id)
                    ->where('stock_opnames.status', 'completed')
                    ->whereBetween('stock_opnames.completed_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->select(
                        'ingredients.id',
                        'ingredients.name',
                        'ingredients.unit',
                        'ingredients.cost_per_unit',
                        \Illuminate\Support\Facades\DB::raw('SUM(ABS(stock_opname_items.difference)) as total_diff_qty'),
                        \Illuminate\Support\Facades\DB::raw('SUM(ABS(stock_opname_items.difference) * ingredients.cost_per_unit) as total_variance_value')
                    )
                    ->groupBy('ingredients.id', 'ingredients.name', 'ingredients.unit', 'ingredients.cost_per_unit')
                    ->orderByDesc('total_variance_value')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ingredient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_diff_qty')
                    ->label('Total Variance Qty')
                    ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->unit),
                Tables\Columns\TextColumn::make('total_variance_value')
                    ->label('Total Variance Value')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
