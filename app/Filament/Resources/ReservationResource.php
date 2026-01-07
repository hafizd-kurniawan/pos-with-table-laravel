<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Filament\Resources\ReservationResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Reservation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReservationResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = '4. Daily Operations';
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('resource.reservation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.reservation.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.reservation.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.reservation.label'))
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.reservation.customer_name')),
                            
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.reservation.phone')),
                            
                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->maxLength(255)
                            ->label(__('resource.reservation.email')),
                    ])->columns(['default' => 1, 'sm' => 3]),

                Forms\Components\Section::make(__('resource.reservation.label'))
                    ->schema([
                        Forms\Components\Select::make('table_id')
                            ->relationship(
                                name: 'table',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query, $get, $livewire) {
                                    // Get current record ID if editing
                                    $currentRecordId = $livewire->record?->id;
                                    
                                    // Filter out tables that have active reservations
                                    // Active = confirmed or checked_in status
                                    return $query->where('tenant_id', auth()->user()->tenant_id)
                                        ->whereDoesntHave('reservations', function ($q) use ($currentRecordId) {
                                        $q->whereIn('status', ['confirmed', 'checked_in'])
                                          ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                              // If editing, exclude current reservation from check
                                              $query->where('id', '!=', $currentRecordId);
                                          });
                                    });
                                }
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->label(__('resource.reservation.table'))
                            ->helperText(__('resource.reservation.helpers.table'))
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                // Show table info in dropdown
                                $status = match($record->status) {
                                    'available' => '✅',
                                    'occupied' => '👥',
                                    'reserved' => '🔒',
                                    'maintenance' => '🔧',
                                    default => '❓'
                                };
                                return "{$status} {$record->name} (Cap: {$record->capacity}) - {$record->category?->name}";
                            })
                            ->rules([
                                function ($get, $livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                        if (!$value) return;
                                        
                                        $reservationDate = $get('reservation_date');
                                        $reservationTime = $get('reservation_time');
                                        $currentRecordId = $livewire->record?->id;
                                        
                                        if (!$reservationDate || !$reservationTime) return;
                                        
                                        // Check if table has overlapping reservation
                                        $hasConflict = \App\Models\Reservation::where('table_id', $value)
                                            ->whereIn('status', ['confirmed', 'checked_in'])
                                            ->where('reservation_date', $reservationDate)
                                            ->where('reservation_time', $reservationTime)
                                            ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                                $query->where('id', '!=', $currentRecordId);
                                            })
                                            ->exists();
                                        
                                        if ($hasConflict) {
                                            $table = \App\Models\Table::find($value);
                                            $fail(__('resource.reservation.messages.conflict', ['table' => $table->name]));
                                        }
                                    };
                                }
                            ]),
                            
                        Forms\Components\TextInput::make('party_size')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->label(__('resource.reservation.party_size')),
                            
                        Forms\Components\DatePicker::make('reservation_date')
                            ->required()
                            ->label(__('resource.reservation.reservation_date'))
                            ->native(false)
                            ->minDate(now())
                            ->displayFormat('d/m/Y')
                            ->helperText(__('resource.reservation.helpers.reservation_date'))
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // When date changes, revalidate table selection
                                $set('table_id', null);
                            }),
                            
                        Forms\Components\TimePicker::make('reservation_time')
                            ->required()
                            ->label(__('resource.reservation.reservation_time'))
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->helperText(__('resource.reservation.helpers.reservation_time'))
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // When time changes, revalidate table selection
                                $set('table_id', null);
                            }),
                            
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' =>(__('resource.reservation.statuses.pending')),
                                'confirmed' =>(__('resource.reservation.statuses.confirmed')),
                                'checked_in' =>(__('resource.reservation.statuses.checked_in')),
                                'completed' =>(__('resource.reservation.statuses.completed')),
                                'cancelled' =>(__('resource.reservation.statuses.cancelled')),
                                'no_show' =>(__('resource.reservation.statuses.no_show'))
                            ])
                            ->default('pending')
                            ->label(__('resource.reservation.status')),
                    ])->columns(['default' => 1, 'sm' => 3]),

                Forms\Components\Section::make(__('resource.general.additional_info'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('resource.reservation.notes'))
                            ->helperText(__('resource.reservation.helpers.notes'))
                            ->rows(3),
                            
                        Forms\Components\Textarea::make('special_requests')
                            ->label(__('resource.reservation.special_requests'))
                            ->helperText(__('resource.reservation.helpers.special_requests'))
                            ->rows(3),
                    ])->columns(['default' => 1, 'sm' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('table.name')
                    ->label(__('resource.reservation.table'))
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label(__('resource.reservation.customer_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('reservation_date')
                    ->label(__('resource.reservation.reservation_date'))
                    ->date('M j, Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('resource.reservation.status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'checked_in', 
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no_show',
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'pending',
                        'heroicon-m-check-circle' => 'confirmed',
                        'heroicon-m-arrow-right-circle' => 'checked_in',
                        'heroicon-m-check-badge' => 'completed',
                        'heroicon-m-x-circle' => 'cancelled',
                        'heroicon-m-exclamation-triangle' => 'no_show',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('resource.reservation.statuses.pending'),
                        'confirmed' => __('resource.reservation.statuses.confirmed'),
                        'checked_in' => __('resource.reservation.statuses.checked_in'),
                        'completed' => __('resource.reservation.statuses.completed'),
                        'cancelled' => __('resource.reservation.statuses.cancelled'),
                        'no_show' => __('resource.reservation.statuses.no_show'),
                        default => ucfirst($state),
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('reservation_time')
                    ->label(__('resource.reservation.reservation_time'))
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('H:i'))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('party_size')
                    ->label(__('resource.reservation.party_size'))
                    ->numeric()
                    ->suffix(' people')
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label(__('resource.reservation.phone'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resource.reservation.created_at'))
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('reservation_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('resource.reservation.status'))
                    ->options([
                        'pending' => __('resource.reservation.statuses.pending'),
                        'confirmed' => __('resource.reservation.statuses.confirmed'),
                        'checked_in' => __('resource.reservation.statuses.checked_in'),
                        'completed' => __('resource.reservation.statuses.completed'),
                        'cancelled' => __('resource.reservation.statuses.cancelled'),
                        'no_show' => __('resource.reservation.statuses.no_show')
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('today')
                    ->label(__('resource.reservation.filters.today'))
                    ->query(fn (Builder $query) => $query->whereDate('reservation_date', today())),

                Tables\Filters\Filter::make('upcoming')
                    ->label(__('resource.reservation.filters.upcoming'))
                    ->query(fn (Builder $query) => $query->where('reservation_date', '>=', today())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('confirm')
                        ->label(__('resource.reservation.actions.confirm.label'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'confirmed']))
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.reservation.actions.confirm.heading'))
                        ->modalDescription(fn (Reservation $record) => __('resource.reservation.actions.confirm.description', ['name' => $record->customer_name]))
                        ->visible(fn (Reservation $record) => $record->status === 'pending')
                        ->successNotificationTitle(__('resource.reservation.actions.confirm.success_title'))
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('resource.reservation.actions.confirm.success_title'))
                                ->body(__('resource.reservation.actions.confirm.success_body', ['table' => $record->table->name, 'name' => $record->customer_name]))
                                ->send();
                        }),

                    Tables\Actions\Action::make('checkin')
                        ->label(__('resource.reservation.actions.checkin.label'))
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'checked_in']))
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.reservation.actions.checkin.heading'))
                        ->modalDescription(fn (Reservation $record) => __('resource.reservation.actions.checkin.description', ['name' => $record->customer_name]))
                        ->visible(fn (Reservation $record) => $record->status === 'confirmed')
                        ->successNotificationTitle(__('resource.reservation.actions.checkin.success_title'))
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('resource.reservation.actions.checkin.success_title'))
                                ->body(__('resource.reservation.actions.checkin.success_body', ['name' => $record->customer_name, 'table' => $record->table->name]))
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('complete')
                        ->label(__('resource.reservation.actions.complete.label'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'completed']))
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.reservation.actions.complete.heading'))
                        ->modalDescription(fn (Reservation $record) => __('resource.reservation.actions.complete.description'))
                        ->visible(fn (Reservation $record) => in_array($record->status, ['confirmed', 'checked_in']))
                        ->successNotificationTitle(__('resource.reservation.actions.complete.success_title'))
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('resource.reservation.actions.complete.success_title'))
                                ->body(__('resource.reservation.actions.complete.success_body', ['table' => $record->table->name]))
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('cancel')
                        ->label(__('resource.reservation.actions.cancel.label'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.reservation.actions.cancel.heading'))
                        ->modalDescription(fn (Reservation $record) => __('resource.reservation.actions.cancel.description', ['name' => $record->customer_name]))
                        ->visible(fn (Reservation $record) => in_array($record->status, ['pending', 'confirmed']))
                        ->successNotificationTitle(__('resource.reservation.actions.cancel.success_title'))
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title(__('resource.reservation.actions.cancel.success_title'))
                                ->body(__('resource.reservation.actions.cancel.success_body', ['name' => $record->customer_name]))
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('no_show')
                        ->label(__('resource.reservation.actions.no_show.label'))
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'no_show']))
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.reservation.actions.no_show.heading'))
                        ->modalDescription(fn (Reservation $record) => __('resource.reservation.actions.no_show.description', ['name' => $record->customer_name]))
                        ->visible(fn (Reservation $record) => in_array($record->status, ['confirmed']))
                        ->successNotificationTitle(__('resource.reservation.actions.no_show.success_title'))
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title(__('resource.reservation.actions.no_show.success_title'))
                                ->body(__('resource.reservation.actions.no_show.success_body', ['name' => $record->customer_name]))
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make()
                        ->label(__('resource.general.actions.delete.label'))
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading(__('resource.general.actions.delete.heading', ['label' => __('resource.reservation.label')]))
                        ->modalDescription(__('resource.general.actions.delete.description'))
                        ->successNotificationTitle(__('resource.general.actions.delete.success_title', ['label' => __('resource.reservation.label')])),
                ])->tooltip('Quick Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
        ];
    }
}
