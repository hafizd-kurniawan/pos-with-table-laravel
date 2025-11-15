<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name', fn ($query) => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')  // ← Hanya supplier ACTIVE!
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('Select supplier'),
                Forms\Components\TextInput::make('po_number')
                    ->label('PO Number')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn() => 'Auto-generated')
                    ->helperText('PO number will be auto-generated on save'),
                
                Forms\Components\DatePicker::make('order_date')
                    ->label('Order Date')
                    ->default(now())
                    ->required(),
                
                Forms\Components\DatePicker::make('expected_delivery_date')
                    ->label('Expected Delivery'),
                
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(function ($record) {
                        // Don't allow manually setting to 'received' - must use Receive button!
                        $options = [
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'cancelled' => 'Cancelled',
                        ];
                        
                        // If already received, show it but disabled
                        if ($record && $record->status === 'received') {
                            $options['received'] = 'Received';
                        }
                        
                        return $options;
                    })
                    ->default('draft')
                    ->required()
                    ->disabled(fn ($record) => $record && $record->status === 'received')
                    ->helperText('⚠️ To receive PO, use "Receive" button in list, NOT this dropdown!'),
                
                Forms\Components\TextInput::make('tax')
                    ->label('Tax')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                
                Forms\Components\TextInput::make('discount')
                    ->label('Discount')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                
                Forms\Components\TextInput::make('shipping_cost')
                    ->label('Shipping Cost')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
                
                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('ingredient_id')
                                    ->label('Ingredient')
                                    ->options(function() {
                                        return \App\Models\Ingredient::where('tenant_id', auth()->user()->tenant_id)
                                            ->where('status', 'active')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => $item->name . ' (' . $item->sku . ') - Stock: ' . $item->current_stock . ' ' . $item->unit
                                            ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $ingredient = \App\Models\Ingredient::find($state);
                                            if ($ingredient) {
                                                $set('unit_price', $ingredient->cost_per_unit);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantity = $state ?? 0;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $set('subtotal', $quantity * $unitPrice);
                                    })
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantity = $get('quantity') ?? 0;
                                        $unitPrice = $state ?? 0;
                                        $set('subtotal', $quantity * $unitPrice);
                                    })
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Rp')
                                    ->columnSpan(1),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label('Item Notes')
                                    ->rows(1)
                                    ->columnSpan(5),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->createItemButtonLabel('Add Item')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['ingredient_id']) 
                                    ? \App\Models\Ingredient::find($state['ingredient_id'])?->name . ' - ' . \App\Helpers\FormatHelper::formatCurrency($state['subtotal'] ?? 0)
                                    : 'New Item'
                            ),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Expected Delivery')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'received',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('Received By')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('actual_delivery_date')
                    ->label('Received Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'sent')
                    ->form([
                        Forms\Components\Select::make('received_by')
                            ->label('Diterima Oleh')
                            ->options(function() {
                                return \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
                                    ->pluck('name', 'id');
                            })
                            ->default(auth()->id())
                            ->required()
                            ->searchable()
                            ->helperText('Pilih user yang menerima barang ini'),
                        
                        Forms\Components\DatePicker::make('actual_delivery_date')
                            ->label('Tanggal Diterima')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->receive($data['received_by'] ?? auth()->id());
                        
                        // Update actual delivery date if provided
                        if (isset($data['actual_delivery_date'])) {
                            $record->update(['actual_delivery_date' => $data['actual_delivery_date']]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PO Received!')
                            ->body('Stock telah diupdate untuk semua items.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Receive Purchase Order')
                    ->modalDescription('Stock akan otomatis bertambah untuk semua items di PO ini.')
                    ->modalSubmitActionLabel('Receive'),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
