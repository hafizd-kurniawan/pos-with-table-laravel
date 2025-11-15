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
    protected static ?string $navigationGroup = 'Table Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Customer Name'),
                            
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->label('Phone Number'),
                            
                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->maxLength(255)
                            ->label('Email Address'),
                    ])->columns(3),

                Forms\Components\Section::make('Reservation Details')
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
                                    return $query->whereDoesntHave('reservations', function ($q) use ($currentRecordId) {
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
                            ->label('Table')
                            ->helperText('Only available tables are shown (tables with active reservations are hidden)')
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
                                            $fail("Table {$table->name} is already reserved at this date and time. Please choose another table or time slot.");
                                        }
                                    };
                                }
                            ]),
                            
                        Forms\Components\TextInput::make('party_size')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->label('Party Size'),
                            
                        Forms\Components\DatePicker::make('reservation_date')
                            ->required()
                            ->label('Reservation Date')
                            ->native(false)
                            ->minDate(now())
                            ->displayFormat('d/m/Y')
                            ->helperText('Select future date for reservation')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // When date changes, revalidate table selection
                                $set('table_id', null);
                            }),
                            
                        Forms\Components\TimePicker::make('reservation_time')
                            ->required()
                            ->label('Reservation Time')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->helperText('Time slot in 15-minute intervals')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // When time changes, revalidate table selection
                                $set('table_id', null);
                            }),
                            
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'checked_in' => 'Checked In',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show'
                            ])
                            ->default('pending')
                            ->label('Status'),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->helperText('Notes for staff (not visible to customer)')
                            ->rows(3),
                            
                        Forms\Components\Textarea::make('special_requests')
                            ->label('Special Requests')
                            ->helperText('Customer special requests or dietary requirements')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('table.name')
                    ->label('Table')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('party_size')
                    ->label('Party Size')
                    ->numeric()
                    ->sortable()
                    ->suffix(' people')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('reservation_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reservation_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('H:i'))
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('reservation_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked In',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show'
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('today')
                    ->label('Today\'s Reservations')
                    ->query(fn (Builder $query) => $query->whereDate('reservation_date', today())),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Reservations')
                    ->query(fn (Builder $query) => $query->where('reservation_date', '>=', today())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('confirm')
                        ->label('Confirm')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'confirmed']))
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Reservation')
                        ->modalDescription(fn (Reservation $record) => "Confirm reservation for {$record->customer_name}? Table will be marked as RESERVED.")
                        ->visible(fn (Reservation $record) => $record->status === 'pending')
                        ->successNotificationTitle('Reservation Confirmed!')
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('✅ Reservation Confirmed')
                                ->body("Table {$record->table->name} reserved for {$record->customer_name}")
                                ->send();
                        }),

                    Tables\Actions\Action::make('checkin')
                        ->label('Check In')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'checked_in']))
                        ->requiresConfirmation()
                        ->modalHeading('Check In Customer')
                        ->modalDescription(fn (Reservation $record) => "Check in {$record->customer_name}? Table will be marked as OCCUPIED.")
                        ->visible(fn (Reservation $record) => $record->status === 'confirmed')
                        ->successNotificationTitle('Customer Checked In!')
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('🎉 Customer Checked In')
                                ->body("{$record->customer_name} is now seated at Table {$record->table->name}")
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('complete')
                        ->label('Complete')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'completed']))
                        ->requiresConfirmation()
                        ->modalHeading('Complete Reservation')
                        ->modalDescription(fn (Reservation $record) => "Mark reservation as completed? Table will be made AVAILABLE.")
                        ->visible(fn (Reservation $record) => in_array($record->status, ['confirmed', 'checked_in']))
                        ->successNotificationTitle('Reservation Completed!')
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('✅ Reservation Completed')
                                ->body("Table {$record->table->name} is now available")
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Reservation')
                        ->modalDescription(fn (Reservation $record) => "Cancel reservation for {$record->customer_name}? Table will be made AVAILABLE.")
                        ->visible(fn (Reservation $record) => in_array($record->status, ['pending', 'confirmed']))
                        ->successNotificationTitle('Reservation Cancelled')
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('⚠️ Reservation Cancelled')
                                ->body("Reservation for {$record->customer_name} has been cancelled")
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('no_show')
                        ->label('Mark No Show')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->action(fn (Reservation $record) => $record->update(['status' => 'no_show']))
                        ->requiresConfirmation()
                        ->modalHeading('Mark as No Show')
                        ->modalDescription(fn (Reservation $record) => "Mark {$record->customer_name} as no show? Table will be made AVAILABLE.")
                        ->visible(fn (Reservation $record) => in_array($record->status, ['confirmed']))
                        ->successNotificationTitle('Marked as No Show')
                        ->after(function (Reservation $record) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('⚠️ Customer No Show')
                                ->body("Customer {$record->customer_name} did not show up")
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make()
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Reservation')
                        ->modalDescription('Are you sure you want to delete this reservation? This action cannot be undone.')
                        ->successNotificationTitle('Reservation Deleted'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
        ];
    }
}
