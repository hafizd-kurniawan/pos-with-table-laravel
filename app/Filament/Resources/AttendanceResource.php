<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Forms\Components\Select::make('shift_id')
                    ->label('Current Shift')
                    ->relationship('shift', 'name')
                    ->default(function () {
                        $user = auth()->user();
                        $now = \Carbon\Carbon::now();
                        // Logic to find shift
                        return \App\Models\Shift::where('tenant_id', $user->tenant_id)
                            ->where('start_time', '<=', $now->copy()->addHours(1)->format('H:i:s'))
                            ->orderBy('start_time', 'desc')
                            ->first()?->id 
                            ?? \App\Models\Shift::where('tenant_id', $user->tenant_id)->first()?->id;
                    })
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->default(now())
                    ->readOnly()
                    ->required(),
                Forms\Components\TimePicker::make('clock_in_time')
                    ->default(now())
                    ->readOnly()
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('clock_out_time')
                    ->seconds(false)
                    ->hiddenOn('create'), // Only show on edit/view
                Forms\Components\Hidden::make('late_minutes')
                    ->default(0),
                Forms\Components\Hidden::make('status')
                    ->default('present'),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Selfie (Optional)')
                    ->image()
                    ->directory('attendance')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shift.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clock_in_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('clock_out_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('late_minutes')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        'leave' => 'info',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                        'leave' => 'Leave',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn ($query, $date) => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn ($query, $date) => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AttendanceExport, 'attendance_report.xlsx');
                    }),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
