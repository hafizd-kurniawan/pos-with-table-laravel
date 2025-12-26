<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Filament\Resources\TaxResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationGroup = 'Finance';
    
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('resource.tax.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.tax.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.tax.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.tax.label') . ' Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.tax.name'))
                            ->placeholder(__('resource.tax.placeholders.name'))
                            ->helperText(__('resource.tax.helpers.name')),
                        
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'pajak' => __('resource.tax.types.tax'),
                                'layanan' => __('resource.tax.types.service'),
                            ])
                            ->default('pajak')
                            ->label(__('resource.tax.type'))
                            ->helperText(__('resource.tax.helpers.type')),
                        
                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(11)
                            ->label(__('resource.tax.value'))
                            ->placeholder(__('resource.tax.placeholders.value'))
                            ->helperText(__('resource.tax.helpers.value')),
                        
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => __('resource.tax.statuses.active'),
                                'inactive' => __('resource.tax.statuses.inactive'),
                            ])
                            ->default('active')
                            ->label(__('resource.tax.status'))
                            ->helperText(__('resource.tax.helpers.status')),
                        
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->label(__('resource.tax.description'))
                            ->placeholder(__('resource.tax.placeholders.description'))
                            ->rows(3),
                    ])->columns(['default' => 1, 'sm' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.tax.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label(__('resource.tax.description'))
                    ->limit(50)
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('resource.tax.type'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pajak' => __('resource.tax.types.tax'),
                        'layanan' => __('resource.tax.types.service'),
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'pajak',
                        'success' => 'layanan',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->label(__('resource.tax.value'))
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.tax.status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('resource.tax.statuses.active'),
                        'inactive' => __('resource.tax.statuses.inactive'),
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
