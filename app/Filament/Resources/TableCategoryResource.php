<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableCategoryResource\Pages;
use App\Filament\Resources\TableCategoryResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\TableCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TableCategoryResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = TableCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('resource.table_category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.table_category.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.table_category.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.table_category.label'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.table_category.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('resource.table_category.helpers.name')),

                        Forms\Components\TextInput::make('icon')
                            ->label(__('resource.table_category.icon'))
                            ->maxLength(10)
                            ->helperText(__('resource.table_category.helpers.icon')),

                        Forms\Components\Select::make('color')
                            ->label(__('resource.table_category.color'))
                            ->options([
                                'gray' => __('resource.general.colors.gray'),
                                'red' => __('resource.general.colors.red'),
                                'orange' => __('resource.general.colors.orange'),
                                'amber' => __('resource.general.colors.amber'),
                                'yellow' => __('resource.general.colors.yellow'),
                                'lime' => __('resource.general.colors.lime'),
                                'green' => __('resource.general.colors.green'),
                                'emerald' => __('resource.general.colors.emerald'),
                                'teal' => __('resource.general.colors.teal'),
                                'cyan' => __('resource.general.colors.cyan'),
                                'sky' => __('resource.general.colors.sky'),
                                'blue' => __('resource.general.colors.blue'),
                                'indigo' => __('resource.general.colors.indigo'),
                                'violet' => __('resource.general.colors.violet'),
                                'purple' => __('resource.general.colors.purple'),
                                'fuchsia' => __('resource.general.colors.fuchsia'),
                                'pink' => __('resource.general.colors.pink'),
                                'rose' => __('resource.general.colors.rose'),
                            ])
                            ->default('blue')
                            ->native(false)
                            ->helperText(__('resource.table_category.helpers.color')),

                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.table_category.description'))
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(__('resource.table_category.helpers.description')),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('resource.table_category.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('resource.table_category.helpers.sort_order')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('resource.table_category.is_active'))
                            ->default(true)
                            ->helperText(__('resource.table_category.helpers.is_active')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.table_category.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('icon')
                    ->label(__('resource.table_category.icon'))
                    ->formatStateUsing(fn ($state) => $state ? $state . ' Icon' : 'No Icon'),

                Tables\Columns\BadgeColumn::make('color')
                    ->label(__('resource.table_category.color'))
                    ->colors([
                        'gray' => 'gray',
                        'red' => 'danger',
                        'orange' => 'warning',
                        'amber' => 'warning',
                        'yellow' => 'warning',
                        'lime' => 'success',
                        'green' => 'success',
                        'emerald' => 'success',
                        'teal' => 'success',
                        'cyan' => 'info',
                        'sky' => 'info',
                        'blue' => 'primary',
                        'indigo' => 'primary',
                        'violet' => 'primary',
                        'purple' => 'primary',
                        'fuchsia' => 'primary',
                        'pink' => 'primary',
                        'rose' => 'danger',
                    ]),

                Tables\Columns\TextColumn::make('tables_count')
                    ->label(__('resource.table_category.tables_count'))
                    ->counts('tables')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('resource.table_category.description'))
                    ->limit(50)
                    ->tooltip(function (TableCategory $record): ?string {
                        if (!$record->description) {
                            return null;
                        }

                        return $record->description;
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('resource.table_category.sort_order'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('resource.table_category.is_active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('resource.table_category.filters.active_status'))
                    ->boolean()
                    ->trueLabel(__('resource.table_category.filters.active_only'))
                    ->falseLabel(__('resource.table_category.filters.inactive_only'))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\TablesRelationManager::class,
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
            'index' => Pages\ListTableCategories::route('/'),
            'create' => Pages\CreateTableCategory::route('/create'),
            'view' => Pages\ViewTableCategory::route('/{record}'),
            'edit' => Pages\EditTableCategory::route('/{record}/edit'),
        ];
    }
}
