<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecipesRelationManager extends RelationManager
{
    protected static string $relationship = 'recipes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ingredient_id')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Ingredient')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $unit = \App\Models\Ingredient::find($state)?->unit;
                            $set('unit_display', $unit);
                        }
                    })
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('unit')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('cost_per_unit')
                            ->numeric()
                            ->required(),
                    ]),
                Forms\Components\TextInput::make('quantity_needed')
                    ->required()
                    ->numeric()
                    ->minValue(0.001)
                    ->label('Quantity Needed')
                    ->suffix(fn (Forms\Get $get) => $get('unit_display') ?? '')
                    ->helperText(fn (Forms\Get $get) => $get('unit_display') ? "Satuan: " . $get('unit_display') : "Pilih ingredient dulu"),
                
                Forms\Components\Hidden::make('unit_display'), // Store unit temporarily for UI
                
                Forms\Components\TextInput::make('notes')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('ingredient.name')
                    ->label('Ingredient')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_needed')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ingredient.unit')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Est. Cost')
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
