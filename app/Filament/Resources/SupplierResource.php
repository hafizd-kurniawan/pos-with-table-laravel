<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    public static function getNavigationGroup(): ?string
    {
        return __('resource.general.navigation.inventory');
    }
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('resource.supplier.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.supplier.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.supplier.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.supplier.label') . ' ' . __('resource.general.information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.supplier.name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('resource.supplier.placeholders.name'))
                            ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 2]),
                        
                        Forms\Components\TextInput::make('code')
                            ->label(__('resource.supplier.code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn() => __('resource.supplier.helpers.auto_generated'))
                            ->helperText(__('resource.supplier.helpers.code'))
                            ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 1]),
                        
                        Forms\Components\Select::make('status')
                            ->label(__('resource.supplier.status'))
                            ->options([
                                'active' => __('resource.general.statuses.active'),
                                'inactive' => __('resource.general.statuses.inactive'),
                            ])
                            ->default('active')
                            ->required()
                            ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 1]),
                    ])->columns(['default' => 1, 'sm' => 2, 'lg' => 4]),
                
                Forms\Components\Section::make(__('resource.supplier.contact_info'))
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label(__('resource.supplier.contact_person'))
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label(__('resource.supplier.phone'))
                            ->tel()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->label(__('resource.supplier.email'))
                            ->email()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('address')
                            ->label(__('resource.supplier.address'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(['default' => 1, 'sm' => 3]),
                
                Forms\Components\Section::make(__('resource.supplier.notes'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('resource.supplier.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.supplier.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resource.supplier.code'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('contact_person')
                    ->label(__('resource.supplier.contact_person'))
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('resource.supplier.phone'))
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label(__('resource.supplier.email'))
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.supplier.status'))
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

                // Hidden columns
                Tables\Columns\TextColumn::make('ingredients_count')
                    ->label(__('resource.ingredient_category.ingredients_count'))
                    ->counts('ingredients')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.supplier.status'))
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
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading(__('resource.general.empty.heading'))
            ->emptyStateDescription(__('resource.general.empty.description'))
            ->emptyStateIcon('heroicon-o-building-storefront');
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
