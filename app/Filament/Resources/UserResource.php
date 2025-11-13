<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 1;

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_users');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_users');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_users');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password')
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->relationship(
                                'role',
                                'name',
                                fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id)
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(3),
                            ])
                            ->helperText('Select the role for this user')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope'),
                
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'success',
                        'Manager' => 'info',
                        'Cashier' => 'warning',
                        'Chef' => 'danger',
                        'Waiter' => 'primary',
                        'Viewer' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('is_login')
                    ->label('Online')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('Filter by Role'),
                
                Tables\Filters\TernaryFilter::make('is_login')
                    ->label('Online Status')
                    ->placeholder('All users')
                    ->trueLabel('Online only')
                    ->falseLabel('Offline only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, User $record) {
                        if ($record->id === auth()->id()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Cannot Delete Own Account')
                                ->body('You cannot delete your own account.')
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
