<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngredientCategoryResource\Pages;
use App\Filament\Resources\IngredientCategoryResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\IngredientCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IngredientCategoryResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = IngredientCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = '1. Setup & Master Data';

    protected static ?int $navigationSort = 2;
    
    public static function getModelLabel(): string
    {
        return __('resource.ingredient_category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.ingredient_category.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.ingredient_category.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.ingredient_category.label') . ' ' . __('resource.general.information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.ingredient_category.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder(__('resource.ingredient_category.placeholders.name'))
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('sku_prefix')
                            ->label(__('resource.ingredient_category.sku_prefix'))
                            ->required()
                            ->maxLength(10)
                            ->placeholder(__('resource.ingredient_category.placeholders.sku_prefix'))
                            ->helperText(__('resource.ingredient_category.helpers.sku_prefix'))
                            ->columnSpan(1),
                        
                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.ingredient_category.description'))
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder(__('resource.ingredient_category.placeholders.description')),
                        
                        Forms\Components\Select::make('status')
                            ->label(__('resource.ingredient_category.status'))
                            ->options([
                                'active' => __('resource.general.statuses.active'),
                                'inactive' => __('resource.general.statuses.inactive'),
                            ])
                            ->default('active')
                            ->required(),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('resource.ingredient_category.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText(__('resource.ingredient_category.helpers.sort_order')),
                    ])->columns(['default' => 1, 'sm' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.ingredient_category.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label(__('resource.ingredient_category.description'))
                    ->searchable()
                    ->limit(50)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sku_prefix')
                    ->label(__('resource.ingredient_category.sku_prefix'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.ingredient_category.status'))
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('resource.general.statuses.active'),
                        'inactive' => __('resource.general.statuses.inactive'),
                        default => $state,
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('ingredients_count')
                    ->label(__('resource.ingredient_category.ingredients_count'))
                    ->counts('ingredients')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('resource.ingredient_category.sort_order'))
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.ingredient_category.status'))
                    ->options([
                        'active' => __('resource.general.statuses.active'),
                        'inactive' => __('resource.general.statuses.inactive'),
                    ]),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
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
            'index' => Pages\ListIngredientCategories::route('/'),
            'create' => Pages\CreateIngredientCategory::route('/create'),
            'edit' => Pages\EditIngredientCategory::route('/{record}/edit'),
        ];
    }
}
