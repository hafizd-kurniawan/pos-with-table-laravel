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
                Forms\Components\Section::make('Discount Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Discount Name'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(1),

                Forms\Components\Section::make('Discount Details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'percentage' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount'
                            ])
                            ->default('percentage')
                            ->reactive()
                            ->label('Discount Type'),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label('Discount Value')
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : '')
                            ->prefix(fn ($get) => $get('type') === 'fixed' ? 'Rp' : '')
                            ->helperText(fn ($get) => $get('type') === 'percentage' 
                                ? 'Enter percentage (0-100)'
                                : 'Enter fixed amount in Rupiah'),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive'
                            ])
                            ->default('active')
                            ->label('Status'),

                        Forms\Components\DatePicker::make('expired_date')
                            ->label('Expiry Date')
                            ->helperText('Leave empty for no expiry date')
                            ->after('today'),
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
                    ->label('Discount Name'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'percentage',
                        'warning' => 'fixed',
                    ])
                    ->label('Type'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn ($record) => 
                        $record->type === 'percentage' 
                            ? $record->value . '%' 
                            : 'Rp ' . number_format($record->value, 0, ',', '.')
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->label('Status'),

                Tables\Columns\TextColumn::make('expired_date')
                    ->date('M j, Y')
                    ->sortable()
                    ->label('Expires')
                    ->placeholder('No expiry'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->label('Created')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
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
