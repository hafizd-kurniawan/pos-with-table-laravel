<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\StockOpname;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockOpnameResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.general.navigation.inventory');
    }
    
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('resource.stock_opname.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.stock_opname.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.stock_opname.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.stock_opname.label') . ' Information')
                    ->schema([
                        Forms\Components\TextInput::make('opname_number')
                            ->label(__('resource.stock_opname.opname_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn() => __('resource.ingredient.helpers.auto_generated')),
                        
                        Forms\Components\DatePicker::make('opname_date')
                            ->label(__('resource.stock_opname.opname_date'))
                            ->default(now())
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->label(__('resource.stock_opname.status'))
                            ->options([
                                'draft' => __('resource.stock_opname.statuses.draft'),
                                'completed' => __('resource.stock_opname.statuses.completed'),
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'completed')
                            ->helperText(__('resource.stock_opname.helpers.status')),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label(__('resource.stock_opname.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(['default' => 1, 'sm' => 2]),
                
                Forms\Components\Section::make(__('resource.stock_opname.items.label'))
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('ingredient_id')
                                    ->label(__('resource.stock_opname.items.ingredient'))
                                    ->options(function() {
                                        return \App\Models\Ingredient::where('tenant_id', auth()->user()->tenant_id)
                                            ->where('status', 'active')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => $item->name . ' (' . $item->sku . ') - Current: ' . $item->current_stock . ' ' . $item->unit
                                            ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $ingredient = \App\Models\Ingredient::find($state);
                                            if ($ingredient) {
                                                $set('system_qty', $ingredient->current_stock);
                                                $set('unit', $ingredient->unit);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                
                                Forms\Components\TextInput::make('system_qty')
                                    ->label(__('resource.stock_opname.items.system_qty'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix(fn ($get) => $get('unit') ?? '')
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('physical_qty')
                                    ->label(__('resource.stock_opname.items.physical_qty'))
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->suffix(fn ($get) => $get('unit') ?? '')
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $systemQty = $get('system_qty') ?? 0;
                                        $physicalQty = $state ?? 0;
                                        $set('difference', $physicalQty - $systemQty);
                                    })
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('difference')
                                    ->label(__('resource.stock_opname.items.difference'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix(fn ($get) => $get('unit') ?? '')
                                    ->columnSpan(1),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('resource.stock_opname.items.notes'))
                                    ->placeholder(__('resource.stock_opname.items.reason_placeholder'))
                                    ->rows(1)
                                    ->columnSpan(5),
                                
                                Forms\Components\Hidden::make('unit'),
                            ])
                            ->columns(['default' => 1, 'md' => 5])
                            ->defaultItems(1)
                            ->createItemButtonLabel(__('resource.stock_opname.items.add_item'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['ingredient_id']) 
                                    ? \App\Models\Ingredient::find($state['ingredient_id'])?->name . ' (Diff: ' . ($state['difference'] ?? 0) . ')'
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
                Tables\Columns\TextColumn::make('opname_number')
                    ->label(__('resource.stock_opname.opname_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('opname_date')
                    ->label(__('resource.stock_opname.opname_date'))
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.stock_opname.status'))
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => __('resource.stock_opname.statuses.draft'),
                        'completed' => __('resource.stock_opname.statuses.completed'),
                        default => ucfirst($state),
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('resource.stock_opname.items_count'))
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('resource.stock_opname.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('resource.stock_opname.completed_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.stock_opname.status'))
                    ->options([
                        'draft' => __('resource.stock_opname.statuses.draft'),
                        'completed' => __('resource.stock_opname.statuses.completed'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label(__('resource.stock_opname.actions.complete.label'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->complete();
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title(__('resource.stock_opname.actions.complete.success_title'))
                            ->body(__('resource.stock_opname.actions.complete.success_body'))
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('resource.stock_opname.actions.complete.heading'))
                    ->modalDescription(fn ($record) => __('resource.stock_opname.actions.complete.description', ['count' => $record->items()->where('difference', '!=', 0)->count()]))
                    ->modalSubmitActionLabel(__('resource.stock_opname.actions.complete.label')),
                
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
        ];
    }
}
