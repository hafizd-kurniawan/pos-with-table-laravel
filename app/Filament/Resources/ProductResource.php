<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;

class ProductResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationGroup = 'Menu';
    
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('resource.product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.product.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.product.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_products');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_products');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_products');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_products');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('resource.product.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('resource.product.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->label(__('resource.product.price'))
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText(__('resource.product.price') . ' jual ke customer'),
                Forms\Components\TextInput::make('cost')
                    ->label(__('resource.product.cost'))
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(1)
                    ->inputMode('decimal')
                    ->helperText(fn ($record) => $record && $record->recipes()->exists() ? 'Calculated automatically from recipes' : __('resource.product.cost_helper'))
                    ->hint(fn ($record) => $record && $record->recipes()->exists() ? 'Auto-sync active' : __('resource.product.cost_hint'))
                    ->readOnly(fn ($record) => $record && $record->recipes()->exists()),
                Forms\Components\FileUpload::make('image')
                    ->label(__('resource.product.image'))
                    ->image(),
                Select::make('status')
                    ->label(__('resource.product.status'))
                    ->required()
                    ->options([
                        'available' => __('resource.product.available'),
                        'unavailable' => __('resource.product.unavailable'),
                    ])
                    ->default('available'),
                Select::make('category_id')
                    ->label(__('resource.product.category'))
                    ->required()
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('stock')
                    ->label(__('resource.product.stock'))
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText(fn ($record) => $record && $record->recipes()->exists() ? 'Calculated automatically from ingredients' : null)
                    ->readOnly(fn ($record) => $record && $record->recipes()->exists()),
                Forms\Components\Toggle::make('is_featured')
                    ->label(__('resource.product.is_featured'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.product.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('resource.product.price'))
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('cost')
                    ->label(__('resource.product.cost'))
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd()
                    ->toggleable()
                    ->color(fn ($record) => $record->cost == 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label(__('resource.product.margin'))
                    ->getStateUsing(function ($record) {
                        if ($record->price <= 0) return '0';
                        $margin = (($record->price - $record->cost) / $record->price) * 100;
                        return $margin == floor($margin) ? \App\Helpers\FormatHelper::formatNumber($margin, 0) : \App\Helpers\FormatHelper::formatNumber($margin, 1);
                    })
                    ->suffix('%')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("((price - cost) / price * 100) {$direction}");
                    })
                    ->alignEnd()
                    ->toggleable()
                    ->color(function ($record) {
                        if ($record->price <= 0) return 'gray';
                        $margin = (($record->price - $record->cost) / $record->price) * 100;
                        return $margin >= 50 ? 'success' : ($margin >= 30 ? 'info' : ($margin >= 20 ? 'warning' : 'danger'));
                    })
                    ->badge(),
                ImageColumn::make('image')
                    ->label(__('resource.product.image'))
                    ->square()
                    ->size(60),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.product.status'))
                    ->colors([
                        'success' => 'available',
                        'danger' => 'unavailable',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('stock')
                    ->label(__('resource.product.stock'))
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock <= 5 ? 'danger' : ($record->stock <= 10 ? 'warning' : 'success'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state . ' pcs'),
                Tables\Columns\IconColumn::make('is_available')
                    ->label(__('resource.product.is_available'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isAvailable())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('resource.product.category'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('resource.product.is_featured'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.product.status'))
                    ->options([
                        'available' => __('resource.product.available'),
                        'unavailable' => __('resource.product.unavailable'),
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('resource.product.category'))
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (≤10)')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 10)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('set_available')
                        ->label(__('resource.product.set_available'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => 'available']));
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('set_unavailable')
                        ->label(__('resource.product.set_unavailable'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => 'unavailable']));
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RecipesRelationManager::class,
            RelationManagers\AddonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
