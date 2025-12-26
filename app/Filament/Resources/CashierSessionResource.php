<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashierSessionResource\Pages;
use App\Filament\Resources\CashierSessionResource\RelationManagers;
use App\Models\CashierSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashierSessionResource extends Resource
{
    protected static ?string $model = CashierSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Shift Information')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('id')->label('Shift ID'),
                        Infolists\Components\TextEntry::make('user.name')->label('Cashier'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'open' => 'success',
                                'closed' => 'gray',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('started_at')->dateTime('d M Y H:i'),
                        Infolists\Components\TextEntry::make('ended_at')->dateTime('d M Y H:i'),
                    ]),

                // SECTION 1: MUTASI KAS (Cash Drawer Reconciliation)
                Infolists\Components\Section::make('Mutasi Kas (Cash Drawer)')
                    ->description('Pergerakan uang fisik di laci kasir.')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('starting_cash')
                            ->label('Modal Awal')
                            ->formatStateUsing(fn ($state) => format_rupiah($state)),
                        
                        Infolists\Components\TextEntry::make('cash_sales')
                            ->label('Penjualan Tunai (Cash Sales)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->state(function ($record) {
                                return \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->where('payment_method', 'cash')
                                    ->sum('total_amount');
                            }),

                        Infolists\Components\TextEntry::make('total_pay_in')
                            ->label('Pemasukan Lain (Pay In)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('total_pay_out')
                            ->label('Pengeluaran (Pay Out)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->color('danger'),

                        Infolists\Components\TextEntry::make('expected_ending_cash')
                            ->label('Seharusnya (Expected)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->state(function ($record) {
                                $cashSales = \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->where('payment_method', 'cash')
                                    ->sum('total_amount');
                                
                                return $record->starting_cash + $cashSales + $record->total_pay_in - $record->total_pay_out;
                            }),

                        Infolists\Components\TextEntry::make('ending_cash')
                            ->label('Aktual di Laci')
                            ->formatStateUsing(fn ($state) => format_rupiah($state)),

                        Infolists\Components\TextEntry::make('variance')
                            ->label('Selisih (Variance)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->color(fn (string $state): string => $state < 0 ? 'danger' : 'success'),
                    ]),

                // SECTION 2: LAPORAN PENJUALAN (Sales Performance)
                Infolists\Components\Section::make('Laporan Penjualan (Sales Performance)')
                    ->description('Ringkasan performa penjualan dari semua metode pembayaran.')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('total_sales')
                            ->label('Gross Sales (Kotor)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->state(function ($record) {
                                return \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->sum('total_amount');
                            }),

                        Infolists\Components\TextEntry::make('total_refunds')
                            ->label('Total Refund')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->color('danger')
                            ->state(function ($record) {
                                return \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->where('status', 'refunded')
                                    ->sum('total_amount');
                            }),
                        
                        Infolists\Components\TextEntry::make('total_tax')
                            ->label('Total Pajak')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->state(function ($record) {
                                return \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->sum('tax_amount');
                            }),

                        Infolists\Components\TextEntry::make('total_service_charge')
                            ->label('Total Service Charge')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->state(function ($record) {
                                return \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->sum('service_charge_amount');
                            }),

                        Infolists\Components\TextEntry::make('total_discount')
                            ->label('Total Diskon')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->state(function ($record) {
                                return \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->sum('discount_amount');
                            }),

                        Infolists\Components\TextEntry::make('net_sales')
                            ->label('Net Sales (Bersih)')
                            ->formatStateUsing(fn ($state) => format_rupiah($state))
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->state(function ($record) {
                                $orders = \App\Models\Order::where('tenant_id', $record->tenant_id)
                                    ->where('created_at', '>=', $record->started_at)
                                    ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                                    ->whereIn('status', ['paid', 'complete', 'completed'])
                                    ->get();
                                return $orders->sum('total_amount') - $orders->sum('tax_amount') - $orders->sum('service_charge_amount');
                            }),
                    ]),

                // SECTION 3: RINCIAN PEMBAYARAN (Payment Breakdown)
                Infolists\Components\Section::make('Rincian Pembayaran (Payment Breakdown)')
                    ->description('Detail penjualan berdasarkan metode pembayaran.')
                    ->schema([
                        Infolists\Components\ViewEntry::make('payment_breakdown')
                            ->view('filament.infolists.payment-breakdown'),
                    ]),

                // SECTION 4: TRANSAKSI & ORDER LIST
                Infolists\Components\Section::make('Transactions (Manual)')
                    ->description('Riwayat Pay In (Tambah Modal) dan Pay Out (Ambil Kas).')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\ViewEntry::make('transactions')
                            ->view('filament.infolists.transactions-list'),
                    ]),

                Infolists\Components\Section::make('Orders List')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\ViewEntry::make('orders_list')
                            ->view('filament.infolists.orders-list'),
                    ]),
                
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes'),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Keep form minimal or read-only if needed, but we rely on Infolist for viewing
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\DateTimePicker::make('started_at')->disabled(),
                Forms\Components\DateTimePicker::make('ended_at')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Shift ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cashier')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->formatStateUsing(fn ($state, $record) => format_rupiah(
                        \App\Models\Order::where('tenant_id', $record->tenant_id)
                            ->where('created_at', '>=', $record->started_at)
                            ->when($record->ended_at, fn($q) => $q->where('created_at', '<=', $record->ended_at))
                            ->whereIn('status', ['paid', 'complete', 'completed'])
                            ->sum('total_amount')
                    ))
                    ->sortable(false), // Calculated, so not sortable by DB
                Tables\Columns\TextColumn::make('cash_sales')
                    ->formatStateUsing(fn ($state) => format_rupiah($state))
                    ->label('Cash Sales')
                    ->sortable(),
                Tables\Columns\TextColumn::make('variance')
                    ->formatStateUsing(fn ($state) => format_rupiah($state))
                    ->label('Variance')
                    ->color(fn (string $state): string => $state < 0 ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\CashierSessionsExport($records),
                                'shifts-' . now()->format('Y-m-d-His') . '.xlsx'
                            );
                        }),
                    Tables\Actions\BulkAction::make('export_pdf')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Collection $records) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashier-session-report', ['sessions' => $records]);
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'shifts-report-' . now()->format('Y-m-d-His') . '.pdf'
                            );
                        }),
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
            'index' => Pages\ListCashierSessions::route('/'),
            'create' => Pages\CreateCashierSession::route('/create'),
            'view' => Pages\ViewCashierSession::route('/{record}'),
            'edit' => Pages\EditCashierSession::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Tenant isolation is handled by BelongsToTenant trait on the model.
        
        // User isolation: If not admin, only show own shifts
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }
}
