<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableResource\Pages;
use App\Filament\Resources\TableResource\RelationManagers;
use App\Filament\Traits\BelongsToTenantResource;
use App\Models\Table as TableModel;
use App\Services\QRCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TableResource extends Resource
{
    use BelongsToTenantResource;

    protected static ?string $model = TableModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Table Management';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Tables';

    protected static ?string $modelLabel = 'Table';

    protected static ?string $pluralModelLabel = 'Tables';

    // Authorization
    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_tables');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_tables');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_tables');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_tables');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Table Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Nama table akan digunakan untuk URL QR Code'),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->required()
                            ->relationship('category', 'name')
                            ->preload()
                            ->native(false)
                            ->helperText('Pilih kategori table untuk memudahkan pengelompokan'),

                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('e.g: Lantai 1, Area Smoking, Dekat Jendela')
                            ->helperText('Lokasi spesifik table untuk memudahkan waiter'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->helperText('Deskripsi tambahan table (fasilitas, view, dll)')
                            ->rows(2),
                            
                        Forms\Components\TextInput::make('qr_code')
                            ->maxLength(255)
                            ->default(null)
                            ->disabled()
                            ->helperText('QR Code akan otomatis di-generate setelah table dibuat'),
                    ])->columns(2),

                Forms\Components\Section::make('Table Configuration')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'available' => 'Available',
                                'occupied' => 'Occupied',
                                'reserved' => 'Reserved',
                                'maintenance' => 'Maintenance',
                            ])
                            ->default('available'),
                            
                        Forms\Components\TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(20)
                            ->helperText('Kapasitas maksimal orang per table'),

                        Forms\Components\TextInput::make('party_size')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(20)
                            ->helperText('Jumlah tamu saat ini (0 = tidak ada tamu)'),

                        Forms\Components\DateTimePicker::make('reservation_time')
                            ->label('Reservation Time')
                            ->helperText('Waktu reservasi table (kosongkan jika tidak ada reservasi)')
                            ->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'currentReservation']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Table Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('category.name')
                    ->label('Category')
                    ->formatStateUsing(function ($record) {
                        $category = $record->category;
                        if (!$category) return 'No Category';
                        return $category->icon . ' ' . $category->name;
                    })
                    ->color(function ($record) {
                        $category = $record->category;
                        if (!$category || !$category->color) return 'gray';
                        return $category->color;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->limit(30)
                    ->tooltip(function (TableModel $record): ?string {
                        return $record->location;
                    })
                    ->toggleable(),
                    
                // NEW: Customer Name from reservation
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('No customer')
                    ->weight('medium')
                    ->icon('heroicon-m-user')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),
                    
                // NEW: Customer Phone from reservation
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable()
                    ->placeholder('No phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->toggleable(),
                    
                Tables\Columns\ImageColumn::make('qr_code_image')
                    ->label('QR Code')
                    ->square()
                    ->size(60)
                    ->getStateUsing(function (TableModel $record) {
                        $tenant = $record->tenant;
                        $url = url("/order/{$tenant->slug}-{$tenant->short_uuid}/{$record->name}");
                        return QRCodeService::generateDataUrl($url, 'svg', 200);
                    })
                    ->tooltip('Preview QR Code')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('qr_url')
                    ->label('Order URL')
                    ->getStateUsing(function (TableModel $record) {
                        return url("/order/{$record->name}");
                    })
                    ->copyable()
                    ->copyMessage('URL berhasil disalin!')
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-link')
                    ->limit(35)
                    ->tooltip('Klik untuk copy URL')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'warning', 
                        'reserved' => 'info',
                        'maintenance' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'available' => 'heroicon-m-check-circle',
                        'occupied' => 'heroicon-m-user-group',
                        'reserved' => 'heroicon-m-clock',
                        'maintenance' => 'heroicon-m-wrench-screwdriver',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                    
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->sortable()
                    ->suffix(' orang')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('party_size')
                    ->label('Current Party')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => 
                        $state > 0 ? "{$state}/{$record->capacity} people" : 'Empty'
                    )
                    ->badge()
                    ->color(fn ($state, $record) => match(true) {
                        $state == 0 => 'gray',
                        $state >= $record->capacity => 'danger', 
                        $state >= ($record->capacity * 0.8) => 'warning',
                        default => 'success'
                    })
                    ->icon(fn ($state) => $state > 0 ? 'heroicon-m-users' : 'heroicon-m-user-minus')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('reservation_time')
                    ->label('Reserved Until')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->placeholder('No reservation')
                    ->icon('heroicon-m-clock')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->tooltip(function (TableModel $record) {
                        if (!$record->reservation_time) return null;
                        $reservation = $record->currentReservation;
                        if (!$reservation) return 'Reserved until: ' . $record->reservation_time->format('M j, Y H:i');
                        return "Reservation by: {$reservation->customer_name}\nParty Size: {$reservation->party_size} people\nStatus: " . ucfirst($reservation->status);
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'reserved' => 'Reserved',
                        'maintenance' => 'Maintenance',
                    ])
                    ->multiple()
                    ->native(false),

                Tables\Filters\Filter::make('has_location')
                    ->label('With Location')
                    ->query(fn (Builder $query) => $query->whereNotNull('location')),

                Tables\Filters\Filter::make('has_reservation')
                    ->label('Has Reservation')
                    ->query(fn (Builder $query) => $query->whereNotNull('reservation_time')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                    
                // Action::make('printQR')
                //     ->label('Print QR')
                //     ->icon('heroicon-o-printer')
                //     ->color('success')
                //     ->tooltip('Print QR Code untuk table ini')
                //     ->action(function (TableModel $record) {
                //         // Generate QR code for printing
                //         $url = url("/order/{$record->name}");
                        
                //         // Update qr_code field
                //         $record->update(['qr_code' => $url]);
                        
                //         // Redirect to print page
                //         return redirect()->route('table.print-qr', $record->id);
                //     })
                //     ->requiresConfirmation()
                //     ->modalHeading('Print QR Code')
                //     ->modalDescription(fn (TableModel $record) => "Print QR code untuk Table: {$record->name}?\n\nURL yang akan di-generate: " . url("/order/{$record->name}"))
                //     ->modalSubmitActionLabel('🖨️ Print QR Code')
                //     ->modalIcon('heroicon-o-qr-code'),
                    
                Action::make('generateQR')
                    ->label('Generate QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->tooltip('Generate atau update QR Code')
                    ->action(function (TableModel $record) {
                        $tenant = $record->tenant;
                        $url = url("/order/{$tenant->slug}-{$tenant->short_uuid}/{$record->name}");
                        $record->update(['qr_code' => $url]);
                        
                        Notification::make()
                            ->title('QR Code Generated! ✅')
                            ->body(fn (TableModel $record) => new \Illuminate\Support\HtmlString(
                                "QR code untuk Table <strong>{$record->name}</strong> berhasil di-generate!"
                                . "<br><br>URL: {$url}"
                            ))                            
                            ->success()
                            ->duration(5000)
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('View Print Page')
                                    ->url(route('table.print-qr', $record->id))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate QR Code')
                    ->modalDescription(fn (TableModel $record) => new \Illuminate\Support\HtmlString(
                        nl2br(
                            "Generate QR code untuk Table: {$record->name}?\n\n" .
                            "URL yang akan di-generate:\n" . url("/order/{$record->tenant->slug}-{$record->tenant->short_uuid}/{$record->name}") . "\n\n" .
                            "Customer dapat scan QR code ini untuk langsung order ke table tersebut."
                        )
                    ))
                    ->modalSubmitActionLabel('Generate QR Code')
                    ->modalIcon('heroicon-o-sparkles'),
                    
                Action::make('downloadQR')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->tooltip('Download QR Code sebagai file')
                    ->url(fn (TableModel $record) => route('table.download-qr', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('generateAllQR')
                        ->label('🎯 Generate All QR Codes')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->action(function ($records) {
                            $successCount = 0;
                            $urls = [];
                            
                            foreach ($records as $record) {
                                $tenant = $record->tenant;
                                $url = url("/order/{$tenant->slug}-{$tenant->short_uuid}/{$record->name}");
                                $record->update(['qr_code' => $url]);
                                $urls[] = "Table {$record->name}: {$url}";
                                $successCount++;
                            }
                            
                            Notification::make()
                                ->title("🎉 Bulk QR Generation Complete!")
                                ->body("**{$successCount} QR codes** berhasil di-generate!\n\n📋 Tables yang di-update:\n" . implode("\n", array_slice($urls, 0, 5)) . ($successCount > 5 ? "\n... dan " . ($successCount - 5) . " lainnya" : ""))
                                ->success()
                                ->duration(8000)
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('viewTables')
                                        ->label('View Tables')
                                        ->url(route('filament.admin.resources.tables.index')),
                                ])
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('🎯 Generate QR Codes for Selected Tables')
                        ->modalDescription(fn ($records) => "Generate QR codes untuk **" . count($records) . " tables** yang dipilih?\n\n📱 Setiap table akan mendapat QR code dengan URL:\n`" . url('/order/{table-name}') . "`\n\n✨ QR codes dapat langsung digunakan untuk customer order.")
                        ->modalSubmitActionLabel('🚀 Generate All QR Codes')
                        ->modalIcon('heroicon-o-sparkles'),
                        
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('📝 Update Status')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'available' => 'Available',
                                    'occupied' => 'Occupied',
                                    'reserved' => 'Reserved',
                                    'maintenance' => 'Maintenance',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                                $count++;
                            }
                            
                            Notification::make()
                                ->title('Status Updated! ✅')
                                ->body("Status **{$count} tables** berhasil diubah menjadi: **{$data['status']}**")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada Tables 🍽️')
            ->emptyStateDescription('Buat table pertama untuk mulai menerima order!')
            ->emptyStateIcon('heroicon-o-building-storefront');
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
            'index' => Pages\ListTables::route('/'),
            'create' => Pages\CreateTable::route('/create'),
            'edit' => Pages\EditTable::route('/{record}/edit'),
        ];
    }
}
