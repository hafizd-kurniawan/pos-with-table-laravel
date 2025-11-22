<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngredientResource\Pages;
use App\Filament\Resources\IngredientResource\RelationManagers;
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
    protected static ?string $model = Ingredient::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Premium Rice, Sugar'),
                
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn() => 'Auto-generated')
                    ->helperText('SKU will be generated based on category'),
                
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('ingredientCategory', 'name', fn ($query) => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                        ->ordered()
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('Select category'),
                
                Forms\Components\Select::make('unit')
                    ->label('Unit')
                    ->options(function() {
                        return \App\Models\Unit::where('tenant_id', auth()->user()->tenant_id)
                            ->where('status', 'active')
                            ->orderBy('sort_order')
                            ->pluck('name', 'symbol');
                    })
                    ->searchable()
                    ->required()
                    ->placeholder('kg, L, pcs, etc'),
                
                Forms\Components\TextInput::make('current_stock')
                    ->label('Current Stock')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state, $record) => 
                        $record ? \App\Helpers\FormatHelper::formatStock($state) : '0'
                    )
                    ->suffix(fn ($record) => $record?->unit ?? '')
                    ->helperText('Stock updates automatically from Purchase Orders'),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('min_stock')
                            ->label('Min Stock (Alert)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->suffix(fn ($get) => $get('unit') ?? '')
                            ->helperText('Alert when stock falls below this')
                            ->placeholder('e.g., 10 or 10.5'),
                        
                        Forms\Components\TextInput::make('max_stock')
                            ->label('Max Stock')
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn ($get) => $get('unit') ?? '')
                            ->helperText('Maximum stock level (optional)')
                            ->placeholder('e.g., 100 or 100.5'),
                    ]),
                
                Forms\Components\TextInput::make('cost_per_unit')
                    ->label('Cost per Unit')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp')
                    ->helperText('Purchase price per unit')
                    ->placeholder('e.g., 15000'),
                
                Forms\Components\Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name', fn ($query) => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('Select supplier'),
                
                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->maxSize(2048),
                
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
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
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('ingredientCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state, $record) => 
                        \App\Helpers\FormatHelper::formatStock($state) . ' ' . $record->unit
                    )
                    ->sortable()
                    ->alignEnd(),
                
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min')
                    ->formatStateUsing(fn ($state, $record) => 
                        \App\Helpers\FormatHelper::formatStock($state) . ' ' . $record->unit
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('stock_status')
                    ->label('Stock Status')
                    ->colors([
                        'success' => 'safe',
                        'warning' => 'low',
                        'danger' => 'critical',
                        'secondary' => 'out_of_stock',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                
                Tables\Columns\TextColumn::make('cost_per_unit')
                    ->label('Cost/Unit')
                    ->formatStateUsing(fn ($state) => 
                        \App\Helpers\FormatHelper::formatCurrency($state)
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Stock Value')
                    ->formatStateUsing(fn ($state) => 
                        \App\Helpers\FormatHelper::formatCurrency($state)
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'safe' => 'Safe',
                        'low' => 'Low',
                        'critical' => 'Critical',
                        'out_of_stock' => 'Out of Stock',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
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
            ->emptyStateHeading('No ingredients yet')
            ->emptyStateDescription('Create your first ingredient to get started.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
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
