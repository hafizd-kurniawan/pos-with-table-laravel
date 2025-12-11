<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Filament\Resources\UnitResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('resource.unit.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.unit.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.unit.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('manage_units');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('manage_units');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('manage_units');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('manage_units');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.unit.label') . ' Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.unit.name'))
                            ->placeholder(__('resource.unit.placeholders.name')),
                        
                        Forms\Components\TextInput::make('symbol')
                            ->required()
                            ->maxLength(10)
                            ->label(__('resource.unit.symbol'))
                            ->placeholder(__('resource.unit.placeholders.symbol'))
                            ->unique(ignoreRecord: true)
                            ->helperText(__('resource.unit.helpers.symbol')),
                        
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'weight' => __('resource.unit.types.weight'),
                                'volume' => __('resource.unit.types.volume'),
                                'count' => __('resource.unit.types.count'),
                                'general' => __('resource.unit.types.general'),
                            ])
                            ->default('general')
                            ->label(__('resource.unit.type')),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(255)
                            ->label(__('resource.unit.description'))
                            ->columnSpanFull(),
                        
                        Forms\Components\Section::make(__('resource.unit.label') . ' Settings')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->options([
                                        'active' => __('resource.unit.statuses.active'),
                                        'inactive' => __('resource.unit.statuses.inactive'),
                                    ])
                                    ->default('active')
                                    ->label(__('resource.unit.status')),
                                
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->label(__('resource.unit.sort_order'))
                                    ->helperText(__('resource.unit.helpers.sort_order')),
                            ])->columns(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('resource.unit.name'))
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('symbol')
                    ->searchable()
                    ->label(__('resource.unit.symbol'))
                    ->badge()
                    ->color('gray')
                    ->copyable(),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('resource.unit.type'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'weight' => __('resource.unit.types.weight'),
                        'volume' => __('resource.unit.types.volume'),
                        'count' => __('resource.unit.types.count'),
                        'general' => __('resource.unit.types.general'),
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'weight',
                        'info' => 'volume',
                        'success' => 'count',
                        'secondary' => 'general',
                    ]),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.unit.status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('resource.unit.statuses.active'),
                        'inactive' => __('resource.unit.statuses.inactive'),
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label(__('resource.unit.sort_order'))
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label(__('resource.general.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'weight' => __('resource.unit.types.weight'),
                        'volume' => __('resource.unit.types.volume'),
                        'count' => __('resource.unit.types.count'),
                        'general' => __('resource.unit.types.general'),
                    ])
                    ->label(__('resource.unit.type')),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => __('resource.unit.statuses.active'),
                        'inactive' => __('resource.unit.statuses.inactive'),
                    ])
                    ->label(__('resource.unit.status')),
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
