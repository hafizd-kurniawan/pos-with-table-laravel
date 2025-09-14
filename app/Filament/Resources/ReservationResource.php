<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Filament\Resources\ReservationResource\RelationManagers;
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
                            ->relationship('table', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Table')
                            ->helperText('Select available table for reservation'),
                            
                        Forms\Components\TextInput::make('party_size')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->label('Party Size'),
                            
                        Forms\Components\DatePicker::make('reservation_date')
                            ->required()
                            ->label('Reservation Date')
                            ->after('today'),
                            
                        Forms\Components\TimePicker::make('reservation_time')
                            ->required()
                            ->label('Reservation Time')
                            ->seconds(false),
                            
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
                    ->time('H:i')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Reservation $record) => $record->update(['status' => 'confirmed']))
                    ->visible(fn (Reservation $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('checkin')
                    ->label('Check In')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('info')
                    ->action(fn (Reservation $record) => $record->update(['status' => 'checked_in']))
                    ->visible(fn (Reservation $record) => $record->status === 'confirmed'),
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
