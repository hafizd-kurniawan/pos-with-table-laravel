<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('resource.role.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.role.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.role.plural_label');
    }

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_roles');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_roles');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_roles');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_roles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resource.role.label') . ' Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state)))
                            ->label(__('resource.role.name'))
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->dehydrated()
                            ->columnSpan(1),
                        
                        Forms\Components\Textarea::make('description')
                            ->label(__('resource.role.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_default')
                            ->label(__('resource.role.is_default'))
                            ->helperText(__('resource.role.helpers.is_default'))
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->columnSpan(1),
                        
                        Forms\Components\Toggle::make('is_system')
                            ->label(__('resource.role.is_system'))
                            ->helperText(__('resource.role.helpers.is_system'))
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make(__('resource.role.permissions'))
                    ->description(__('resource.role.helpers.permissions'))
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('')
                            ->relationship('permissions', 'name')
                            ->options(function () {
                                // Get permissions grouped by group with formatted labels
                                $grouped = [];
                                $permissions = Permission::all()->groupBy('group');
                                
                                foreach ($permissions as $group => $perms) {
                                    foreach ($perms as $perm) {
                                        $grouped[$perm->id] = '[' . strtoupper($group) . '] ' . $perm->name;
                                    }
                                }
                                
                                return $grouped;
                            })
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label(__('resource.role.name'))
                    ->icon('heroicon-o-shield-check'),
                
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->label(__('resource.role.description'))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('resource.user.plural_label'))
                    ->counts('users')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('resource.role.permissions'))
                    ->counts('permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('resource.role.is_default'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_system')
                    ->label(__('resource.role.is_system'))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('resource.general.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label(__('resource.role.is_default'))
                    ->placeholder('All roles')
                    ->trueLabel('Default roles only')
                    ->falseLabel('Non-default roles'),
                
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label(__('resource.role.is_system'))
                    ->placeholder('All roles')
                    ->trueLabel('System roles only')
                    ->falseLabel('Custom roles'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Role $record) {
                        if ($record->is_system) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Cannot Delete System Role')
                                ->body(__('resource.role.messages.cannot_delete_system'))
                                ->send();
                            
                            $action->cancel();
                        }
                        
                        if ($record->users()->count() > 0) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Role Has Users')
                                ->body(__('resource.role.messages.has_users', ['count' => $record->users()->count()]))
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            $systemRoles = $records->where('is_system', true);
                            if ($systemRoles->count() > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Cannot Delete System Roles')
                                    ->body(__('resource.role.messages.cannot_delete_system'))
                                    ->send();
                                
                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
