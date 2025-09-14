<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableCategoryResource\Pages;
use App\Filament\Resources\TableCategoryResource\RelationManagers;
use App\Models\TableCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TableCategoryResource extends Resource
{
    protected static ?string $model = TableCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Table Management';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Table Categories';
    protected static ?string $modelLabel = 'Table Category';
    protected static ?string $pluralModelLabel = 'Table Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Nama kategori table'),

                        Forms\Components\TextInput::make('icon')
                            ->maxLength(10)
                            ->helperText('Emoji atau ikon untuk kategori (contoh: 🪑, 👑, 🚪)'),

                        Forms\Components\Select::make('color')
                            ->options([
                                'gray' => 'Gray',
                                'red' => 'Red',
                                'orange' => 'Orange',
                                'amber' => 'Amber',
                                'yellow' => 'Yellow',
                                'lime' => 'Lime',
                                'green' => 'Green',
                                'emerald' => 'Emerald',
                                'teal' => 'Teal',
                                'cyan' => 'Cyan',
                                'sky' => 'Sky',
                                'blue' => 'Blue',
                                'indigo' => 'Indigo',
                                'violet' => 'Violet',
                                'purple' => 'Purple',
                                'fuchsia' => 'Fuchsia',
                                'pink' => 'Pink',
                                'rose' => 'Rose',
                            ])
                            ->default('blue')
                            ->native(false)
                            ->helperText('Warna badge kategori'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Deskripsi kategori table'),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Urutan tampilan kategori (angka kecil tampil duluan)'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Status aktif kategori'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' Icon' : 'No Icon'),

                Tables\Columns\BadgeColumn::make('color')
                    ->label('Color')
                    ->colors([
                        'gray' => 'gray',
                        'red' => 'danger',
                        'orange' => 'warning',
                        'amber' => 'warning',
                        'yellow' => 'warning',
                        'lime' => 'success',
                        'green' => 'success',
                        'emerald' => 'success',
                        'teal' => 'success',
                        'cyan' => 'info',
                        'sky' => 'info',
                        'blue' => 'primary',
                        'indigo' => 'primary',
                        'violet' => 'primary',
                        'purple' => 'primary',
                        'fuchsia' => 'primary',
                        'pink' => 'primary',
                        'rose' => 'danger',
                    ]),

                Tables\Columns\TextColumn::make('tables_count')
                    ->label('Tables Count')
                    ->counts('tables')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TableCategory $record): ?string {
                        if (!$record->description) {
                            return null;
                        }

                        return $record->description;
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\TablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTableCategories::route('/'),
            'create' => Pages\CreateTableCategory::route('/create'),
            'view' => Pages\ViewTableCategory::route('/{record}'),
            'edit' => Pages\EditTableCategory::route('/{record}/edit'),
        ];
    }
}
