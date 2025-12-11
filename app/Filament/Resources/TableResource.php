<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableResource\Pages;
use App\Filament\Resources\TableResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Table as TableModel;
use App\Services\QRCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TableResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = TableModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('resource.table.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.table.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.table.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_tables');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_tables');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_tables');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_tables');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.table.label') . ' Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resource.table.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('resource.table.helpers.name')),

                        Forms\Components\Select::make('category_id')
                            ->label(__('resource.category.label'))
                            ->required()
                            ->relationship('category', 'name')
                            ->preload()
                            ->native(false)
                            ->helperText(__('resource.table.helpers.category')),

                        Forms\Components\TextInput::make('location')
                            ->label(__('resource.table.location'))
                            ->maxLength(255)
                            ->placeholder(__('resource.table.placeholders.location'))
                            ->helperText(__('resource.table.helpers.location')),
                            
                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.product.description'))
                            ->helperText(__('resource.table.helpers.description'))
                            ->rows(2),
                            
                        Forms\Components\TextInput::make('qr_code')
                            ->label(__('resource.table.qr_code'))
                            ->maxLength(255)
                            ->default(null)
                            ->disabled()
                            ->helperText(__('resource.table.helpers.qr_code')),
                    ])->columns(2),

                Forms\Components\Section::make(__('resource.table.label') . ' Configuration')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('resource.table.status'))
                            ->required()
                            ->options([
                                'available' => __('resource.table.statuses.available'),
                                'occupied' => __('resource.table.statuses.occupied'),
                                'reserved' => __('resource.table.statuses.reserved'),
                                'maintenance' => __('resource.table.statuses.maintenance'),
                            ])
                            ->default('available'),
                            
                        Forms\Components\TextInput::make('capacity')
                            ->label(__('resource.table.capacity'))
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(20)
                            ->helperText(__('resource.table.helpers.capacity')),

                        Forms\Components\TextInput::make('party_size')
                            ->label(__('resource.table.current_party'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(20)
                            ->helperText(__('resource.table.helpers.party_size')),

                        Forms\Components\DateTimePicker::make('reservation_time')
                            ->label(__('resource.table.reservation_time'))
                            ->helperText(__('resource.table.helpers.reservation_time'))
                            ->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'currentReservation']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resource.table.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('category.name')
                    ->label(__('resource.category.label'))
                    ->formatStateUsing(function ($record) {
                        $category = $record->category;
                        if (!$category) return 'No Category';
                        return $category->icon . ' ' . $category->name;
                    })
                    ->color(function ($record) {
                        $category = $record->category;
                        if (!$category || !$category->color) return 'gray';
                        return $category->color;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label(__('resource.table.location'))
                    ->limit(30)
                    ->tooltip(function (TableModel $record): ?string {
                        return $record->location;
                    })
                    ->toggleable(),
                    
                // NEW: Customer Name from reservation
                Tables\Columns\TextColumn::make('customer_name')
                    ->label(__('resource.order.customer_name'))
                    ->searchable()
                    ->placeholder(__('resource.table.placeholders.no_customer'))
                    ->weight('medium')
                    ->icon('heroicon-m-user')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),
                    
                // NEW: Customer Phone from reservation
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label(__('resource.order.customer_phone'))
                    ->searchable()
                    ->placeholder(__('resource.table.placeholders.no_phone'))
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage(__('resource.table.messages.phone_copied'))
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->toggleable(),
                    
                Tables\Columns\ImageColumn::make('qr_code_image')
                    ->label(__('resource.table.qr_code'))
                    ->square()
                    ->size(60)
                    ->getStateUsing(function (TableModel $record) {
                        // Use stored QR code or generate safe URL
                        $url = $record->qr_code;
                        if (empty($url)) {
                            $url = $record->qr_url;
                        }
                        return QRCodeService::generateDataUrl($url, 'svg', 200);
                    })
                    ->tooltip('Preview QR Code')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('qr_url')
                    ->label(__('resource.table.order_url'))
                    ->getStateUsing(function (TableModel $record) {
                        return $record->qr_code ?: $record->qr_url;
                    })
                    ->copyable()
                    ->copyMessage(__('resource.table.messages.url_copied'))
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-link')
                    ->limit(35)
                    ->tooltip('Klik untuk copy URL')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label(__('resource.table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'warning', 
                        'reserved' => 'info',
                        'maintenance' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'available' => 'heroicon-m-check-circle',
                        'occupied' => 'heroicon-m-user-group',
                        'reserved' => 'heroicon-m-clock',
                        'maintenance' => 'heroicon-m-wrench-screwdriver',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => __('resource.table.statuses.available'),
                        'occupied' => __('resource.table.statuses.occupied'),
                        'reserved' => __('resource.table.statuses.reserved'),
                        'maintenance' => __('resource.table.statuses.maintenance'),
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('capacity')
                    ->label(__('resource.table.capacity'))
                    ->numeric()
                    ->sortable()
                    ->suffix(' ' . __('resource.unit.types.count'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('party_size')
                    ->label(__('resource.table.current_party'))
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => 
                        $state > 0 ? "{$state}/{$record->capacity} " . __('resource.unit.types.count') : __('resource.table.placeholders.empty')
                    )
                    ->badge()
                    ->color(fn ($state, $record) => match(true) {
                        $state == 0 => 'gray',
                        $state >= $record->capacity => 'danger', 
                        $state >= ($record->capacity * 0.8) => 'warning',
                        default => 'success'
                    })
                    ->icon(fn ($state) => $state > 0 ? 'heroicon-m-users' : 'heroicon-m-user-minus')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('reservation_time')
                    ->label(__('resource.table.reserved_until'))
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->placeholder(__('resource.table.placeholders.no_reservation'))
                    ->icon('heroicon-m-clock')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->tooltip(function (TableModel $record) {
                        if (!$record->reservation_time) return null;
                        $reservation = $record->currentReservation;
                        if (!$reservation) return 'Reserved until: ' . $record->reservation_time->format('M j, Y H:i');
                        return "Reservation by: {$reservation->customer_name}\nParty Size: {$reservation->party_size}\nStatus: " . ucfirst($reservation->status);
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.general.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resource.general.updated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('resource.category.label'))
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.table.status'))
                    ->options([
                        'available' => __('resource.table.statuses.available'),
                        'occupied' => __('resource.table.statuses.occupied'),
                        'reserved' => __('resource.table.statuses.reserved'),
                        'maintenance' => __('resource.table.statuses.maintenance'),
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('has_location')
                    ->label(__('resource.table.filters.has_location'))
                    ->query(fn (Builder $query) => $query->whereNotNull('location')),

                Tables\Filters\Filter::make('has_reservation')
                    ->label(__('resource.table.filters.has_reservation'))
                    ->query(fn (Builder $query) => $query->whereNotNull('reservation_time')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    
                    Tables\Actions\Action::make('generate_qr')
                        ->label(__('resource.table.actions.generate_qr.label'))
                        ->icon('heroicon-o-qr-code')
                        ->action(function (TableModel $record) {
                            $record->update([
                                'qr_code' => $record->qr_url
                            ]);
                            
                            Notification::make()
                                ->title(__('resource.table.actions.generate_qr.success_title'))
                                ->body(__('resource.table.actions.generate_qr.success_body', ['name' => $record->name]))
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('download_qr')
                        ->label(__('resource.table.actions.download_qr.label'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (TableModel $record) => route('table.download-qr', $record))
                        ->openUrlInNewTab(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('generateAllQR')
                        ->label(__('resource.table.actions.generate_all_qr.label'))
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->action(function ($records) {
                            $successCount = 0;
                            
                            foreach ($records as $record) {
                                $record->update([
                                    'qr_code' => $record->qr_url
                                ]);
                                $successCount++;
                            }
                            
                            Notification::make()
                                ->title(__('resource.table.actions.generate_all_qr.success_title'))
                                ->body(__('resource.table.actions.generate_all_qr.success_body', ['count' => $successCount]))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.table.actions.generate_all_qr.heading'))
                        ->modalDescription(fn ($records) => __('resource.table.actions.generate_all_qr.description', ['count' => count($records)]))
                        ->modalSubmitActionLabel(__('resource.table.actions.generate_all_qr.label'))
                        ->modalIcon('heroicon-o-sparkles'),
                        
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label(__('resource.table.actions.update_status.label'))
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('resource.table.actions.update_status.form_label'))
                                ->options([
                                    'available' => __('resource.table.statuses.available'),
                                    'occupied' => __('resource.table.statuses.occupied'),
                                    'reserved' => __('resource.table.statuses.reserved'),
                                    'maintenance' => __('resource.table.statuses.maintenance'),
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                            
                            Notification::make()
                                ->title('Status Updated!')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading(__('resource.table.empty_state.heading'))
            ->emptyStateDescription(__('resource.table.empty_state.description'))
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
            'index' => Pages\ListTables::route('/'),
            'create' => Pages\CreateTable::route('/create'),
            'edit' => Pages\EditTable::route('/{record}/edit'),
        ];
    }
}
