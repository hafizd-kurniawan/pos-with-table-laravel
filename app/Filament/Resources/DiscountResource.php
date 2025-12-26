<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Filament\Resources\DiscountResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('resource.discount.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.discount.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.discount.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('manage_discounts');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('manage_discounts');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('manage_discounts');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('manage_discounts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.discount.label') . ' Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.discount.name')),
                        
                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.discount.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(['default' => 1, 'sm' => 2]),

                Forms\Components\Section::make(__('resource.discount.label') . ' Details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'percentage' => __('resource.discount.types.percentage'),
                                'fixed' => __('resource.discount.types.fixed')
                            ])
                            ->default('percentage')
                            ->reactive()
                            ->label(__('resource.discount.type')),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label(__('resource.discount.value'))
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : '')
                            ->prefix(fn ($get) => $get('type') === 'fixed' ? 'Rp' : '')
                            ->helperText(fn ($get) => $get('type') === 'percentage' 
                                ? __('resource.discount.helpers.percentage')
                                : __('resource.discount.helpers.fixed')),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => __('resource.discount.statuses.active'),
                                'inactive' => __('resource.discount.statuses.inactive')
                            ])
                            ->default('active')
                            ->label(__('resource.discount.status')),

                        Forms\Components\DatePicker::make('expired_date')
                            ->label(__('resource.discount.expired_date'))
                            ->helperText(__('resource.discount.helpers.expiry'))
                            ->after('today'),
                    ])->columns(['default' => 1, 'sm' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label(__('resource.discount.name')),
                
                Tables\Columns\TextColumn::make('value')
                    ->label(__('resource.discount.value'))
                    ->formatStateUsing(fn ($record) => 
                        $record->type === 'percentage' 
                            ? $record->value . '%' 
                            : \App\Helpers\FormatHelper::formatCurrency($record->value)
                    )
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'percentage',
                        'warning' => 'fixed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => __('resource.discount.types.percentage'),
                        'fixed' => __('resource.discount.types.fixed'),
                        default => $state,
                    })
                    ->label(__('resource.discount.type'))
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('resource.discount.statuses.active'),
                        'inactive' => __('resource.discount.statuses.inactive'),
                        default => $state,
                    })
                    ->label(__('resource.discount.status'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expired_date')
                    ->date('M j, Y')
                    ->sortable()
                    ->label(__('resource.discount.expired_date'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->label(__('resource.general.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => __('resource.discount.types.percentage'),
                        'fixed' => __('resource.discount.types.fixed'),
                    ])
                    ->label(__('resource.discount.type')),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => __('resource.discount.statuses.active'),
                        'inactive' => __('resource.discount.statuses.inactive'),
                    ])
                    ->label(__('resource.discount.status')),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
