<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CriticalAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 6,
    ];

    protected static ?string $heading = '🚨 Critical Alerts';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        $service = new DashboardService();
        $alerts = $service->getCriticalAlerts(5);

        return $table
            ->query(
                \App\Models\Ingredient::query()->whereIn('id', array_column($alerts, 'id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ingredient')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $level = $record->current_stock <= 0 ? 'critical' : 'warning';
                        $icon = $level === 'critical' ? '🔴' : '🟡';
                        return "{$icon} {$state}";
                    }),
                
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', '.') . ' ' . $record->unit)
                    ->color(fn ($record) => $record->current_stock <= 0 ? 'danger' : 'warning'),
                
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', '.') . ' ' . $record->unit),
                
                Tables\Columns\BadgeColumn::make('status_label')
                    ->label('Status')
                    ->formatStateUsing(function ($record) {
                        return $record->current_stock <= 0 ? 'OUT' : 'LOW';
                    })
                    ->colors([
                        'danger' => fn ($record) => $record->current_stock <= 0,
                        'warning' => fn ($record) => $record->current_stock > 0,
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('create_po')
                    ->label('Create PO')
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn () => route('filament.admin.resources.purchase-orders.create'))
                    ->visible(fn ($record) => $record->current_stock <= 0),
                
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.ingredients.edit', $record))
                    ->visible(fn ($record) => $record->current_stock > 0),
            ])
            ->paginated(false);
    }
}
