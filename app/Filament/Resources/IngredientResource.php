<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngredientResource\Pages;
use App\Filament\Resources\IngredientResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Ingredient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IngredientResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Ingredient::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.general.navigation.inventory');
    }
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('resource.ingredient.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.ingredient.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.ingredient.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('resource.ingredient.name'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('resource.ingredient.placeholders.name')),
                
                Forms\Components\TextInput::make('sku')
                    ->label(__('resource.ingredient.sku'))
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn() => __('resource.ingredient.helpers.auto_generated'))
                    ->helperText(__('resource.ingredient.helpers.sku')),
                
                Forms\Components\Select::make('category_id')
                    ->label(__('resource.ingredient.category'))
                    ->relationship('ingredientCategory', 'name', fn ($query) => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                        ->ordered()
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder(__('resource.ingredient.placeholders.category')),
                
                Forms\Components\Select::make('unit')
                    ->label(__('resource.ingredient.unit'))
                    ->options(function() {
                        return \App\Models\Unit::where('tenant_id', auth()->user()->tenant_id)
                            ->where('status', 'active')
                            ->orderBy('sort_order')
                            ->pluck('name', 'symbol');
                    })
                    ->searchable()
                    ->required()
                    ->placeholder(__('resource.ingredient.placeholders.unit')),
                
                Forms\Components\TextInput::make('current_stock')
                    ->label(__('resource.ingredient.current_stock'))
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state, $record) => 
                        $record ? \App\Helpers\FormatHelper::formatStock($state) : '0'
                    )
                    ->suffix(fn ($record) => $record?->unit ?? '')
                    ->helperText(__('resource.ingredient.helpers.current_stock')),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('min_stock')
                            ->label(__('resource.ingredient.min_stock'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->suffix(fn ($get) => $get('unit') ?? '')
                            ->helperText(__('resource.ingredient.helpers.min_stock'))
                            ->placeholder(__('resource.ingredient.placeholders.min_stock')),
                        
                        Forms\Components\TextInput::make('max_stock')
                            ->label(__('resource.ingredient.max_stock'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn ($get) => $get('unit') ?? '')
                            ->helperText(__('resource.ingredient.helpers.max_stock'))
                            ->placeholder(__('resource.ingredient.placeholders.max_stock')),
                    ]),
                
                Forms\Components\TextInput::make('cost_per_unit')
                    ->label(__('resource.ingredient.cost_per_unit'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp')
                    ->helperText(__('resource.ingredient.helpers.cost_per_unit'))
                    ->placeholder(__('resource.ingredient.placeholders.cost_per_unit')),
                
                Forms\Components\Select::make('supplier_id')
                    ->label(__('resource.ingredient.supplier'))
                    ->relationship('supplier', 'name', fn ($query) => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder(__('resource.ingredient.placeholders.supplier')),
                
                Forms\Components\FileUpload::make('image')
                    ->label(__('resource.ingredient.image'))
                    ->image()
                    ->maxSize(2048),
                
                Forms\Components\Textarea::make('description')
                    ->label(__('resource.ingredient.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('status')
                    ->label(__('resource.ingredient.status'))
                    ->options([
                        'active' => __('resource.general.statuses.active'),
                        'inactive' => __('resource.general.statuses.inactive'),
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label(__('resource.ingredient.sku'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.ingredient.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('ingredientCategory.name')
                    ->label(__('resource.ingredient.category'))
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('current_stock')
                    ->label(__('resource.ingredient.current_stock'))
                    ->formatStateUsing(fn ($state, $record) => 
                        \App\Helpers\FormatHelper::formatStock($state) . ' ' . $record->unit
                    )
                    ->sortable()
                    ->alignEnd(),
                
                Tables\Columns\TextColumn::make('min_stock')
                    ->label(__('resource.ingredient.min_stock'))
                    ->formatStateUsing(fn ($state, $record) => 
                        \App\Helpers\FormatHelper::formatStock($state) . ' ' . $record->unit
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('stock_status')
                    ->label(__('resource.ingredient.stock_status'))
                    ->colors([
                        'success' => 'safe',
                        'warning' => 'low',
                        'danger' => 'critical',
                        'secondary' => 'out_of_stock',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'safe' => __('resource.ingredient.stock_statuses.safe'),
                        'low' => __('resource.ingredient.stock_statuses.low'),
                        'critical' => __('resource.ingredient.stock_statuses.critical'),
                        'out_of_stock' => __('resource.ingredient.stock_statuses.out_of_stock'),
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                
                Tables\Columns\TextColumn::make('cost_per_unit')
                    ->label(__('resource.ingredient.cost_per_unit'))
                    ->formatStateUsing(fn ($state) => 
                        \App\Helpers\FormatHelper::formatCurrency($state)
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('stock_value')
                    ->label(__('resource.ingredient.stock_value'))
                    ->formatStateUsing(fn ($state) => 
                        \App\Helpers\FormatHelper::formatCurrency($state)
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('resource.ingredient.supplier'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.ingredient.status'))
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('resource.general.statuses.active'),
                        'inactive' => __('resource.general.statuses.inactive'),
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label(__('resource.ingredient.stock_status'))
                    ->options([
                        'safe' => __('resource.ingredient.stock_statuses.safe'),
                        'low' => __('resource.ingredient.stock_statuses.low'),
                        'critical' => __('resource.ingredient.stock_statuses.critical'),
                        'out_of_stock' => __('resource.ingredient.stock_statuses.out_of_stock'),
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.ingredient.status'))
                    ->options([
                        'active' => __('resource.general.statuses.active'),
                        'inactive' => __('resource.general.statuses.inactive'),
                    ]),
                
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('resource.ingredient.category'))
                    ->relationship('ingredientCategory', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading(__('resource.general.empty.heading'))
            ->emptyStateDescription(__('resource.general.empty.description'))
            ->emptyStateIcon('heroicon-o-cube');
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
            'index' => Pages\ListIngredients::route('/'),
            'create' => Pages\CreateIngredient::route('/create'),
            'edit' => Pages\EditIngredient::route('/{record}/edit'),
        ];
    }
}
