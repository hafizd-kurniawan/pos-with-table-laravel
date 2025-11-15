<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Models\{StockOpname, Ingredient};
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Stock Opname';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Opname Information')
                    ->schema([
                        Forms\Components\DatePicker::make('opname_date')
                            ->label('Opname Date')
                            ->default(now())
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'completed' => 'Completed',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'completed')
                            ->helperText('Use "Complete" action to finalize opname, not this field!'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Items to Count')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('ingredient_id')
                                    ->label('Ingredient')
                                    ->options(function() {
                                        return Ingredient::where('tenant_id', auth()->user()->tenant_id)
                                            ->where('status', 'active')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $ingredient = Ingredient::find($state);
                                            if ($ingredient) {
                                                $set('system_qty', $ingredient->current_stock);
                                                $set('unit', $ingredient->unit);
                                            }
                                        }
                                    }),
                                
                                Forms\Components\TextInput::make('system_qty')
                                    ->label('System Qty')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => 
                                        \App\Helpers\FormatHelper::formatStock($state)
                                    )
                                    ->suffix(fn ($get) => $get('unit') ?? ''),
                                
                                Forms\Components\TextInput::make('physical_qty')
                                    ->label('Physical Count')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->suffix(fn ($get) => $get('unit') ?? ''),
                                
                                Forms\Components\Placeholder::make('difference_display')
                                    ->label('Difference')
                                    ->content(function ($get) {
                                        $system = (float)($get('system_qty') ?? 0);
                                        $physical = (float)($get('physical_qty') ?? 0);
                                        $diff = $physical - $system;
                                        
                                        $sign = $diff > 0 ? '+' : '';
                                        $formatted = \App\Helpers\FormatHelper::formatStock(abs($diff));
                                        
                                        return new \Illuminate\Support\HtmlString(
                                            "<span style='color: " . 
                                            ($diff > 0 ? 'green' : ($diff < 0 ? 'red' : 'gray')) . 
                                            "; font-weight: bold;'>" . 
                                            $sign . $formatted . " " . ($get('unit') ?? '') . 
                                            "</span>"
                                        );
                                    }),
                                
                                Forms\Components\Hidden::make('unit'),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(2)
                                    ->placeholder('Reason for difference (optional)'),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && $record->status === 'completed')
                            ->addActionLabel('Add Ingredient to Count')
                            ->reorderable(false)
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opname_number')
                    ->label('Opname Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('opname_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
                
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'draft' && $record->items()->count() > 0)
                    ->requiresConfirmation()
                    ->modalHeading('Complete Stock Opname')
                    ->modalDescription(function ($record) {
                        $summary = $record->differences_summary;
                        return "This will adjust stock for all items with differences. " .
                               "{$summary['items_with_difference']} items will be adjusted.";
                    })
                    ->action(function ($record) {
                        $record->complete();
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Stock Opname Completed!')
                            ->body('Stock has been adjusted for all items.')
                            ->send();
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($records) => $records && $records->every(fn ($record) => $record->status === 'draft')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
        ];
    }
}
