<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddonsRelationManager extends RelationManager
{
    protected static string $relationship = 'addons';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->step(1)
                    ->default(0),
                Forms\Components\Select::make('ingredient_id')
                    ->label('Ingredient (Optional)')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (!$state) {
                            $set('cost', 0);
                            return;
                        }
                        $ingredient = \App\Models\Ingredient::find($state);
                        if ($ingredient) {
                            $qty = $get('quantity_needed') ?? 0;
                            $set('cost', $ingredient->cost_per_unit * $qty);
                        }
                    }),
                Forms\Components\TextInput::make('quantity_needed')
                    ->label('Quantity Needed')
                    ->numeric()
                    ->default(0)
                    ->step(0.01)
                    ->suffix(function (Forms\Get $get) {
                        $ingredientId = $get('ingredient_id');
                        if ($ingredientId) {
                            $ingredient = \App\Models\Ingredient::find($ingredientId);
                            return $ingredient ? $ingredient->unit : null;
                        }
                        return null;
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $ingredientId = $get('ingredient_id');
                        if ($ingredientId) {
                            $ingredient = \App\Models\Ingredient::find($ingredientId);
                            if ($ingredient) {
                                $set('cost', $ingredient->cost_per_unit * $state);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('cost')
                    ->label('Cost (HPP)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(1)
                    ->readOnly()
                    ->helperText('Calculated from ingredient cost'),
                Forms\Components\Toggle::make('is_available')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state)),
                Tables\Columns\TextColumn::make('cost')
                    ->label('COGS (HPP)')
                    ->formatStateUsing(fn ($state) => \App\Helpers\FormatHelper::formatCurrency($state))
                    ->color('gray'),
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Margin')
                    ->getStateUsing(function ($record) {
                        if ($record->price <= 0) return '0%';
                        $margin = (($record->price - $record->cost) / $record->price) * 100;
                        return \App\Helpers\FormatHelper::formatNumber($margin, 1) . '%';
                    })
                    ->color(function ($record) {
                        if ($record->price <= 0) return 'gray';
                        $margin = (($record->price - $record->cost) / $record->price) * 100;
                        return $margin >= 50 ? 'success' : ($margin >= 30 ? 'info' : ($margin >= 20 ? 'warning' : 'danger'));
                    })
                    ->badge(),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean(),
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
