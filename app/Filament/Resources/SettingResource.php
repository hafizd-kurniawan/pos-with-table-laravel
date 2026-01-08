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

    protected static ?string $navigationGroup = '6. System Settings';
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('resource.setting.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.setting.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.setting.plural_label');
    }

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
                Forms\Components\Section::make(__('resource.setting.label') . ' Details')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.setting.key'))
                            ->helperText(__('resource.setting.helpers.key')),
                        
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->label(__('resource.setting.label_field')),
                        
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'text' => __('resource.setting.types.text'),
                                'textarea' => __('resource.setting.types.textarea'),
                                'boolean' => __('resource.setting.types.boolean'),
                                'select' => __('resource.setting.types.select'),
                                'color' => __('resource.setting.types.color'),
                                'file' => __('resource.setting.types.file'),
                                'number' => __('resource.setting.types.number'),
                                'email' => __('resource.setting.types.email'),
                                'url' => __('resource.setting.types.url'),
                            ])
                            ->reactive()
                            ->label(__('resource.setting.type')),
                        
                        Forms\Components\Select::make('group')
                            ->required()
                            ->options([
                                'general' => __('resource.setting.groups.general'),
                                'order' => __('resource.setting.groups.order'),
                                'appearance' => __('resource.setting.groups.appearance'),
                                'payment' => __('resource.setting.groups.payment'),
                                'notification' => __('resource.setting.groups.notification'),
                                'loyalty' => 'Loyalty Program',
                            ])
                            ->label(__('resource.setting.group')),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->label(__('resource.discount.description')) // Reusing description label
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'sm' => 2]),
                
                Forms\Components\Section::make('Value Configuration')
                    ->schema([
                        Forms\Components\Textarea::make('value_text')
                            ->label(__('resource.setting.value'))
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array($get('type'), ['text', 'textarea', 'email', 'url', 'number'])),
                        
                        Forms\Components\Toggle::make('value_boolean')
                            ->label(__('resource.setting.value'))
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->formatStateUsing(fn ($state) => filter_var($state, FILTER_VALIDATE_BOOLEAN))
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),
                        
                        Forms\Components\ColorPicker::make('value_color')
                            ->label(__('resource.setting.value'))
                            ->visible(fn ($get) => $get('type') === 'color'),
                        
                        Forms\Components\FileUpload::make('value_file')
                            ->label(__('resource.setting.value'))
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

                        Forms\Components\Select::make('value_select')
                            ->label(__('resource.setting.value'))
                            ->visible(fn ($get) => $get('type') === 'select')
                            ->options(function ($get, $record) {
                                // Try to get options from form state first (if editing options)
                                $options = $get('options');
                                
                                // If not in state, try to get from record
                                if (empty($options) && $record) {
                                    $options = $record->options;
                                }
                                
                                // If options is string (JSON), decode it
                                if (is_string($options)) {
                                    $options = json_decode($options, true);
                                }
                                
                                return is_array($options) ? $options : [];
                            }),
                        
                        Forms\Components\KeyValue::make('options')
                            ->label(__('resource.setting.options'))
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
                    ->weight('bold')
                    ->label(__('resource.setting.label_field')),
                
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray')
                    ->label(__('resource.setting.key')),
                    
                Tables\Columns\TextColumn::make('value')
                    ->limit(50)
                    ->label(__('resource.setting.value'))
                    ->formatStateUsing(function ($state, $record) {
                        if (is_array($state)) {
                            return json_encode($state);
                        }
                        return $state;
                    })
                    ->color('gray'),

                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'primary',
                        'order' => 'success',
                        'appearance' => 'warning',
                        'payment' => 'danger',
                        'notification' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => __('resource.setting.groups.general'),
                        'order' => __('resource.setting.groups.order'),
                        'appearance' => __('resource.setting.groups.appearance'),
                        'payment' => __('resource.setting.groups.payment'),
                        'notification' => __('resource.setting.groups.notification'),
                        default => $state,
                    })
                    ->label(__('resource.setting.group'))
                    ->sortable(),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => __('resource.setting.groups.general'),
                        'order' => __('resource.setting.groups.order'),
                        'appearance' => __('resource.setting.groups.appearance'),
                        'payment' => __('resource.setting.groups.payment'),
                        'notification' => __('resource.setting.groups.notification'),
                        'loyalty' => 'Loyalty Program',
                    ])
                    ->label(__('resource.setting.group')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('resource.setting.empty_state.heading'))
            ->emptyStateDescription(__('resource.setting.empty_state.description'))
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