<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
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
    use BelongsToTenantResource;

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.general.navigation.inventory');
    }
    
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('resource.purchase_order.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.purchase_order.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.purchase_order.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->label(__('resource.purchase_order.supplier'))
                    ->relationship('supplier', 'name', fn ($query) => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder(__('resource.ingredient.placeholders.supplier')),
                Forms\Components\TextInput::make('po_number')
                    ->label(__('resource.purchase_order.po_number'))
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn() => __('resource.ingredient.helpers.auto_generated'))
                    ->helperText(__('resource.purchase_order.helpers.po_number')),
                
                Forms\Components\DatePicker::make('order_date')
                    ->label(__('resource.purchase_order.order_date'))
                    ->default(now())
                    ->required(),
                
                Forms\Components\DatePicker::make('expected_delivery_date')
                    ->label(__('resource.purchase_order.expected_delivery_date')),
                
                Forms\Components\Select::make('status')
                    ->label(__('resource.purchase_order.status'))
                    ->options(function ($record) {
                        $options = [
                            'draft' => __('resource.purchase_order.statuses.draft'),
                            'sent' => __('resource.purchase_order.statuses.sent'),
                            'cancelled' => __('resource.purchase_order.statuses.cancelled'),
                        ];
                        
                        if ($record && $record->status === 'received') {
                            $options['received'] = __('resource.purchase_order.statuses.received');
                        }
                        
                        return $options;
                    })
                    ->default('draft')
                    ->required()
                    ->disabled(fn ($record) => $record && $record->status === 'received')
                    ->helperText(__('resource.purchase_order.helpers.status')),
                
                Forms\Components\TextInput::make('tax')
                    ->label(__('resource.purchase_order.tax'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                
                Forms\Components\TextInput::make('discount')
                    ->label(__('resource.purchase_order.discount'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                
                Forms\Components\TextInput::make('shipping_cost')
                    ->label(__('resource.purchase_order.shipping_cost'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                
                Forms\Components\Textarea::make('notes')
                    ->label(__('resource.purchase_order.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
                
                Forms\Components\Section::make(__('resource.purchase_order.items.label'))
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('ingredient_id')
                                    ->label(__('resource.purchase_order.items.ingredient'))
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
                                    ->label(__('resource.purchase_order.items.quantity'))
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
                                    ->label(__('resource.purchase_order.items.unit_price'))
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
                                    ->label(__('resource.purchase_order.items.subtotal'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Rp')
                                    ->columnSpan(1),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('resource.purchase_order.items.notes'))
                                    ->rows(1)
                                    ->columnSpan(5),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->createItemButtonLabel(__('resource.purchase_order.items.add_item'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['ingredient_id']) 
                                    ? \App\Models\Ingredient::find($state['ingredient_id'])?->name . ' - ' . \App\Helpers\FormatHelper::formatCurrency($state['subtotal'] ?? 0)
                                    : __('resource.general.new_item')
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
                    ->label(__('resource.purchase_order.po_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('resource.purchase_order.supplier'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('order_date')
                    ->label(__('resource.purchase_order.order_date'))
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label(__('resource.purchase_order.expected_delivery_date'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.purchase_order.status'))
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'received',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => __('resource.purchase_order.statuses.draft'),
                        'sent' => __('resource.purchase_order.statuses.sent'),
                        'received' => __('resource.purchase_order.statuses.received'),
                        'cancelled' => __('resource.purchase_order.statuses.cancelled'),
                        default => ucfirst($state),
                    }),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('resource.purchase_order.total_amount'))
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('resource.purchase_order.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('receiver.name')
                    ->label(__('resource.purchase_order.received_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('actual_delivery_date')
                    ->label(__('resource.purchase_order.actual_delivery_date'))
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resource.general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('resource.general.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.purchase_order.status'))
                    ->options([
                        'draft' => __('resource.purchase_order.statuses.draft'),
                        'sent' => __('resource.purchase_order.statuses.sent'),
                        'received' => __('resource.purchase_order.statuses.received'),
                        'cancelled' => __('resource.purchase_order.statuses.cancelled'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('receive')
                    ->label(__('resource.purchase_order.actions.receive.label'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'sent')
                    ->form([
                        Forms\Components\Select::make('received_by')
                            ->label(__('resource.purchase_order.received_by'))
                            ->options(function() {
                                return \App\Models\User::where('tenant_id', auth()->user()->tenant_id)
                                    ->pluck('name', 'id');
                            })
                            ->default(auth()->id())
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\DatePicker::make('actual_delivery_date')
                            ->label(__('resource.purchase_order.actual_delivery_date'))
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
                            ->title(__('resource.purchase_order.actions.receive.success_title'))
                            ->body(__('resource.purchase_order.actions.receive.success_body'))
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('resource.purchase_order.actions.receive.heading'))
                    ->modalDescription(__('resource.purchase_order.actions.receive.description'))
                    ->modalSubmitActionLabel(__('resource.purchase_order.actions.receive.submit')),
                
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
