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
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Harga jual ke customer'),
                Forms\Components\TextInput::make('cost')
                    ->label('Cost (COGS)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(1)
                    ->inputMode('decimal')
                    ->helperText('Harga pokok/biaya bahan (untuk profit tracking)')
                    ->hint('💡 Isi sesuai actual cost untuk profit analysis yang akurat'),
                Forms\Components\FileUpload::make('image')
                    ->image(),
                Select::make('status')
                    ->required()
                    ->options([
                        'available' => 'Available',
                        'unavailable' => 'Unavailable',
                    ])
                    ->default('available'),
                Select::make('category_id')
                    ->required()
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_featured')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('COGS')
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd()
                    ->toggleable()
                    ->color(fn ($record) => $record->cost == 0 ? 'danger' : 'success')
                    ->tooltip(fn ($record) => $record->cost == 0 ? 'Update COGS untuk profit tracking' : 'COGS sudah diset'),
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Margin')
                    ->getStateUsing(function ($record) {
                        if ($record->price <= 0) return '0';
                        $margin = (($record->price - $record->cost) / $record->price) * 100;
                        // Format: hapus .00 di belakang jika integer, keep 1 decimal jika ada
                        return $margin == floor($margin) ? number_format($margin, 0) : number_format($margin, 1);
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
                    ->square()
                    ->size(60),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'danger' => 'unavailable',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock <= 5 ? 'danger' : ($record->stock <= 10 ? 'warning' : 'success'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state . ' pcs'),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isAvailable())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'unavailable' => 'Unavailable',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (≤10)')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 10)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->status === 'available' ? 'Set Unavailable' : 'Set Available')
                    ->icon(fn ($record) => $record->status === 'available' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->status === 'available' ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update([
                            'status' => $record->status === 'available' ? 'unavailable' : 'available'
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Change Product Status')
                    ->modalDescription(fn ($record) => 
                        'Are you sure you want to set this product as ' . 
                        ($record->status === 'available' ? 'unavailable' : 'available') . '?'
                    ),
                Tables\Actions\Action::make('add_stock')
                    ->label('Add Stock')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity to Add')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->action(function ($record, array $data) {
                        $record->increaseStock($data['quantity']);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('set_available')
                        ->label('Set Available')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => 'available']));
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('set_unavailable')
                        ->label('Set Unavailable')
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
            //
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
