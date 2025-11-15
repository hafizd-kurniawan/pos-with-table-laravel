<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Filament\Resources\TaxResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationGroup = 'Finance';
    
    protected static ?int $navigationSort = 2;

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('manage_taxes');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Pajak/Biaya')
                    ->placeholder('Contoh: PPN 11%, Biaya Layanan')
                    ->helperText('Nama yang akan muncul di struk'),
                
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'pajak' => 'Pajak (Tax) - Contoh: PPN',
                        'layanan' => 'Biaya Layanan (Service Charge)',
                    ])
                    ->default('pajak')
                    ->label('Jenis')
                    ->helperText('Pajak = PPN/PPh, Layanan = Service Charge'),
                
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(11)
                    ->label('Persentase')
                    ->placeholder('11')
                    ->helperText('Nilai dalam persen (%). Contoh: 11 untuk 11%'),
                
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'active' => '✅ Aktif (Diterapkan di transaksi)',
                        'inactive' => '❌ Tidak Aktif (Tidak diterapkan)',
                    ])
                    ->default('active')
                    ->label('Status')
                    ->helperText('Hanya pajak/biaya aktif yang diterapkan'),
                
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Keterangan (Opsional)')
                    ->placeholder('Contoh: PPN 11% sesuai peraturan pemerintah')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pajak' => 'Pajak',
                        'layanan' => 'Biaya Layanan',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'pajak',
                        'success' => 'layanan',
                    ]),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Persentase')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
