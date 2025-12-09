<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Operations';
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('resource.order.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.order.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.order.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_orders');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_orders');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_orders');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('resource.order.code'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('table_id')
                    ->label(__('resource.order.table'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('resource.order.total_amount'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->label(__('resource.order.status'))
                    ->required(),
                Forms\Components\DateTimePicker::make('placed_at')
                    ->label(__('resource.order.placed_at'))
                    ->required(),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label(__('resource.order.completed_at')),
                Forms\Components\TextInput::make('payment_method')
                    ->label(__('resource.order.payment_method'))
                    ->required()
                    ->maxLength(255)
                    ->default('qris'),
                Forms\Components\Textarea::make('notes')
                    ->label(__('resource.order.notes'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('customer_name')
                    ->label(__('resource.order.customer_name'))
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('customer_phone')
                    ->label(__('resource.order.customer_phone'))
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('customer_email')
                    ->label(__('resource.order.customer_email'))
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('qr_string')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('meta')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('payment_url')
                    ->label(__('resource.order.payment_url'))
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resource.order.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('table_id')
                    ->label(__('resource.order.table'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('resource.order.total_amount'))
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('resource.order.status')),
                Tables\Columns\TextColumn::make('placed_at')
                    ->label(__('resource.order.placed_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('resource.order.payment_method'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label(__('resource.order.customer_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label(__('resource.order.customer_phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_email')
                    ->label(__('resource.order.customer_email'))
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('payment_url')
                    ->label(__('resource.order.payment_url'))
                    ->searchable(),
            ])
            ->defaultSort('placed_at', 'desc')
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
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
