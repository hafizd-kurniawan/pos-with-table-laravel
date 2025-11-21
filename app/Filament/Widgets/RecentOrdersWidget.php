<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Filament\Resources\OrderResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 9;
    
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '🧾 Recent Orders';

    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        $service = new DashboardService();
        $orders = $service->getRecentOrders(5);

        return $table
            ->query(
                \App\Models\Order::query()
                    ->whereIn('id', array_column($orders, 'id'))
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('time')
                    ->label('Time')
                    ->formatStateUsing(fn ($record) => $record->created_at->format('H:i')),
                
                Tables\Columns\TextColumn::make('code')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->formatStateUsing(fn ($state) => $state ?? '#' . $this->id),
                
                Tables\Columns\TextColumn::make('table.name')
                    ->label('Table/Customer')
                    ->default('Takeaway')
                    ->formatStateUsing(fn ($state) => $state ?? 'Takeaway'),
                
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->alignCenter()
                    ->counts('orderItems')
                    ->formatStateUsing(fn ($state) => $state . ' items'),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'completed' => '✅ Completed',
                        'cooking' => '🍳 Cooking',
                        'pending' => '⏱️ Pending',
                        'cancelled' => '❌ Cancelled',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'cooking',
                        'gray' => 'pending',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
