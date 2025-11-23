<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SettingResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?string $modelLabel = 'Setting';

    protected static ?string $pluralModelLabel = 'Settings';

    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 3;

    // Authorization: Check permissions
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_settings');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('edit_settings');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_settings');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('edit_settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Details')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Key can be edited. Be careful as it is used throughout the system!'),
                        
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'text' => 'Text Input',
                                'textarea' => 'Textarea',
                                'boolean' => 'Boolean (Yes/No)',
                                'select' => 'Select Dropdown',
                                'color' => 'Color Picker',
                                'file' => 'File Upload',
                                'number' => 'Number',
                                'email' => 'Email',
                                'url' => 'URL',
                            ])
                            ->reactive(),
                        
                        Forms\Components\Select::make('group')
                            ->required()
                            ->options([
                                'general' => 'General',
                                'order' => 'Order Settings',
                                'appearance' => 'Appearance',
                                'payment' => 'Payment',
                                'notification' => 'Notification',
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Value Configuration')
                    ->schema([
                        Forms\Components\Textarea::make('value')
                            ->label('Setting Value')
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array($get('type'), ['text', 'textarea', 'email', 'url', 'number'])),
                        
                        Forms\Components\Toggle::make('value')
                            ->label('Setting Value')
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->formatStateUsing(fn ($state) => filter_var($state, FILTER_VALIDATE_BOOLEAN))
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),
                        
                        Forms\Components\ColorPicker::make('value')
                            ->label('Setting Value')
                            ->visible(fn ($get) => $get('type') === 'color'),
                        
                        Forms\Components\FileUpload::make('value')
                            ->label('Setting Value')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->directory('settings')
                            ->visibility('public')
                            ->visible(fn ($get) => $get('type') === 'file'),
                        
                        Forms\Components\KeyValue::make('options')
                            ->label('Select Options')
                            ->keyLabel('Option Value')
                            ->valueLabel('Option Label')
                            ->visible(fn ($get) => $get('type') === 'select')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                            ->dehydrateStateUsing(fn ($state) => is_array($state) ? $state : null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('value')
                    ->limit(50)
                    ->formatStateUsing(function ($state, $record) {
                        if (is_array($state)) {
                            return json_encode($state);
                        }
                        return $state;
                    })
                    ->tooltip(function ($record) {
                        $value = $record->value;
                        if (is_array($value)) {
                            return json_encode($value, JSON_PRETTY_PRINT);
                        }
                        return $value;
                    }),
                
                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'primary',
                        'order' => 'success',
                        'appearance' => 'warning',
                        'payment' => 'danger',
                        'notification' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'order' => 'Order Settings',
                        'appearance' => 'Appearance',
                        'payment' => 'Payment',
                        'notification' => 'Notification',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Settings Found')
            ->emptyStateDescription('Create your first setting to configure the application.')
            ->emptyStateIcon('heroicon-o-cog-6-tooth');
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'view' => Pages\ViewSetting::route('/{record}'),
            'edit' => Pages\EditSettingSimple::route('/{record}/edit'),
        ];
    }
}