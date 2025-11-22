<x-filament-panels::page>
    {{-- ApexCharts CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <div class="space-y-6">
        
        {{-- REPORT TYPE SELECTOR WITH EXPORT BUTTONS --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tipe Laporan
                    </label>
                    <select wire:model.live="reportType" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="daily">Harian</option>
                        <option value="period">Periode</option>
                    </select>
                </div>
                
                @if($reportType === 'daily')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tanggal
                        </label>
                        <input type="date" wire:model.live="selectedDate"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="flex items-end">
                        <button wire:click="generateCache" 
                                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                            🔄 Generate Cache
                        </button>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tanggal Mulai
                        </label>
                        <input type="date" wire:model.live="startDate"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tanggal Selesai
                        </label>
                        <input type="date" wire:model.live="endDate"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                @endif
            </div>
            
            {{-- EXPORT SECTION --}}
            <div class="mt-6 pt-6 border-t-2 border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">📥 Ekspor Laporan</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Download laporan dalam format PDF atau Excel</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- PDF Export Card --}}
                    <div class="relative group">
                        <button wire:click="exportPdf" 
                                wire:loading.attr="disabled"
                                wire:target="exportPdf"
                                class="w-full p-4 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-2 border-red-200 dark:border-red-700 rounded-xl hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center text-white shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 text-left">
                                    <h4 class="text-base font-bold text-red-900 dark:text-red-100 mb-1">
                                        Export PDF
                                        <span wire:loading wire:target="exportPdf" class="ml-2 text-xs">
                                            <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </h4>
                                    <p class="text-xs text-red-700 dark:text-red-300 mb-2">
                                        Format profesional untuk print & share
                                    </p>
                                    <div class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="font-mono">
                                            @if($reportType === 'daily')
                                                laporan-{{ $selectedDate }}.pdf
                                            @else
                                                laporan-{{ $startDate }}_{{ $endDate }}.pdf
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>

                    {{-- Excel Export Card --}}
                    <div class="relative group">
                        <button wire:click="exportExcel" 
                                wire:loading.attr="disabled"
                                wire:target="exportExcel"
                                class="w-full p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-2 border-green-200 dark:border-green-700 rounded-xl hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 text-left">
                                    <h4 class="text-base font-bold text-green-900 dark:text-green-100 mb-1">
                                        Export Excel
                                        <span wire:loading wire:target="exportExcel" class="ml-2 text-xs">
                                            <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </h4>
                                    <p class="text-xs text-green-700 dark:text-green-300 mb-2">
                                        Data terstruktur dalam 3 sheets
                                    </p>
                                    <div class="flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="font-mono">
                                            @if($reportType === 'daily')
                                                laporan-{{ $selectedDate }}.xlsx
                                            @else
                                                laporan-{{ $startDate }}_{{ $endDate }}.xlsx
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Export Info --}}
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-xs text-blue-800 dark:text-blue-200">
                            <p class="font-semibold mb-1">💡 Tips:</p>
                            <ul class="space-y-1 ml-4 list-disc">
                                <li><strong>PDF</strong> - Cocok untuk print, presentasi, dan share via WhatsApp</li>
                                <li><strong>Excel</strong> - Cocok untuk analisis lebih lanjut, pivot table, dan perhitungan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DAILY REPORT --}}
        @if($reportType === 'daily' && $dailySummary)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Total Orders --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #3B82F6, #2563EB);">
                    <div class="text-sm opacity-90">Total Order</div>
                    <div class="text-3xl font-bold mt-2">{{ $dailySummary['summary']['total_orders'] }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ $dailySummary['summary']['total_items'] }} items terjual</div>
                </div>

                {{-- Gross Sales --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #10B981, #059669);">
                    <div class="text-sm opacity-90">Penjualan Kotor</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($dailySummary['summary']['gross_sales'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">Sebelum diskon</div>
                </div>

                {{-- Discount --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #F59E0B, #D97706);">
                    <div class="text-sm opacity-90">Total Diskon</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($dailySummary['summary']['total_discount'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">Potongan harga</div>
                </div>

                {{-- Net Sales --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #8B5CF6, #7C3AED);">
                    <div class="text-sm opacity-90">Penjualan Bersih</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($dailySummary['summary']['net_sales'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">Final amount</div>
                </div>
            </div>

            {{-- Revenue Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Rincian Pendapatan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['subtotal'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Pajak (Tax)</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['total_tax'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Service Charge</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['total_service'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Rata-rata Transaksi</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['average_transaction'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Total Items</span>
                            <span class="font-semibold dark:text-white">{{ $dailySummary['summary']['total_items'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Total Customers</span>
                            <span class="font-semibold dark:text-white">{{ $dailySummary['summary']['total_customers'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CHARTS SECTION --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Sales Trend Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📈 Tren Penjualan</h3>
                    <div id="salesTrendChart" style="min-height: 300px;"></div>
                </div>
                
                {{-- Payment Method Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💳 Distribusi Pembayaran</h3>
                    <div id="paymentChart" style="min-height: 300px;"></div>
                </div>
            </div>

            {{-- Payment Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Metode Pembayaran</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($dailySummary['payment_breakdown'] as $payment)
                        <div class="border dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase">{{ $payment['method'] }}</span>
                                <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">{{ $payment['percentage'] }}%</span>
                            </div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                Rp {{ number_format($payment['amount'], 0, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $payment['count'] }} transaksi
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- DISCOUNT & TAX BREAKDOWN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Discount Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💰 Rincian Diskon</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Total Diskon Diberikan</span>
                            <span class="text-xl font-bold text-orange-600 dark:text-orange-400">
                                Rp {{ number_format($dailySummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Penjualan Kotor</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($dailySummary['summary']['gross_sales'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Setelah Diskon</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($dailySummary['summary']['gross_sales'] - $dailySummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-3 pt-3 border-t dark:border-gray-700">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Persentase Diskon</span>
                                <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full font-semibold">
                                    {{ $dailySummary['summary']['gross_sales'] > 0 ? number_format(($dailySummary['summary']['total_discount'] / $dailySummary['summary']['gross_sales']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Tax & Service Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🧾 Rincian Pajak & Biaya</h3>
                    <div class="space-y-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">PPN (Tax)</span>
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($dailySummary['summary']['total_tax'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-300">
                                Base: Rp {{ number_format($dailySummary['summary']['subtotal'], 0, ',', '.') }}
                            </div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">Service Charge</span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    Rp {{ number_format($dailySummary['summary']['total_service'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-300">
                                Base: Rp {{ number_format($dailySummary['summary']['subtotal'], 0, ',', '.') }}
                            </div>
                        </div>
                        
                        <div class="mt-3 pt-3 border-t dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Total Biaya Tambahan</span>
                                <span class="text-xl font-bold text-purple-600 dark:text-purple-400">
                                    Rp {{ number_format($dailySummary['summary']['total_tax'] + $dailySummary['summary']['total_service'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- PERIOD REPORT --}}
        @if($reportType === 'period' && $periodSummary)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Total Orders --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #3B82F6, #2563EB);">
                    <div class="text-sm opacity-90">Total Order</div>
                    <div class="text-3xl font-bold mt-2">{{ $periodSummary['summary']['total_orders'] }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ $periodSummary['period']['days'] }} hari</div>
                </div>

                {{-- Net Sales --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #10B981, #059669);">
                    <div class="text-sm opacity-90">Penjualan Bersih</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($periodSummary['summary']['net_sales'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">Total periode</div>
                </div>

                {{-- Growth --}}
                @php
                    $isGrowthUp = $periodSummary['comparison']['growth']['trend'] === 'up';
                    $growthGradient = $isGrowthUp 
                        ? 'background: linear-gradient(to bottom right, #10B981, #059669);' 
                        : 'background: linear-gradient(to bottom right, #EF4444, #DC2626);';
                @endphp
                <div class="rounded-lg shadow-lg p-6 text-white" style="{{ $growthGradient }}">
                    <div class="text-sm opacity-90">Pertumbuhan</div>
                    <div class="text-3xl font-bold mt-2">
                        {{ $isGrowthUp ? '↑' : '↓' }}
                        {{ abs($periodSummary['comparison']['growth']['percentage']) }}%
                    </div>
                    <div class="text-xs opacity-75 mt-1 uppercase">{{ $periodSummary['comparison']['growth']['status'] }}</div>
                </div>

                {{-- Average Transaction --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #8B5CF6, #7C3AED);">
                    <div class="text-sm opacity-90">Rata-rata</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($periodSummary['summary']['average_transaction'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">Per transaksi</div>
                </div>
            </div>

            {{-- Comparison --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Perbandingan dengan Periode Sebelumnya</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Periode Sekarang</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mb-1">
                            {{ \Carbon\Carbon::parse($periodSummary['period']['start'])->format('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($periodSummary['period']['end'])->format('d M Y') }}
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($periodSummary['summary']['net_sales'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Periode Sebelumnya</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mb-1">
                            {{ \Carbon\Carbon::parse($periodSummary['comparison']['previous_period']['start'])->format('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($periodSummary['comparison']['previous_period']['end'])->format('d M Y') }}
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($periodSummary['comparison']['previous_period']['net_sales'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Selisih</span>
                        <span class="text-xl font-bold {{ $periodSummary['comparison']['growth']['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $periodSummary['comparison']['growth']['trend'] === 'up' ? '+' : '' }}
                            Rp {{ number_format($periodSummary['comparison']['growth']['amount'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- CHARTS SECTION FOR PERIOD --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Sales Trend Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📈 Tren Penjualan</h3>
                    <div id="salesTrendChart" style="min-height: 300px;"></div>
                </div>
                
                {{-- Payment Method Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💳 Distribusi Pembayaran</h3>
                    <div id="paymentChart" style="min-height: 300px;"></div>
                </div>
            </div>

            {{-- Payment Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Metode Pembayaran</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($periodSummary['payment_breakdown'] as $payment)
                        <div class="border dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase">{{ $payment['method'] }}</span>
                                <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">{{ $payment['percentage'] }}%</span>
                            </div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                Rp {{ number_format($payment['amount'], 0, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $payment['count'] }} transaksi
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- DISCOUNT & TAX BREAKDOWN FOR PERIOD --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Discount Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💰 Rincian Diskon</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Total Diskon Diberikan</span>
                            <span class="text-xl font-bold text-orange-600 dark:text-orange-400">
                                Rp {{ number_format($periodSummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Penjualan Kotor</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($periodSummary['summary']['gross_sales'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Setelah Diskon</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($periodSummary['summary']['gross_sales'] - $periodSummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-3 pt-3 border-t dark:border-gray-700">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Persentase Diskon</span>
                                <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full font-semibold">
                                    {{ $periodSummary['summary']['gross_sales'] > 0 ? number_format(($periodSummary['summary']['total_discount'] / $periodSummary['summary']['gross_sales']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Tax & Service Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🧾 Rincian Pajak & Biaya</h3>
                    <div class="space-y-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">PPN (Tax)</span>
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($periodSummary['summary']['total_tax'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-300">
                                Base: Rp {{ number_format($periodSummary['summary']['subtotal'], 0, ',', '.') }}
                            </div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">Service Charge</span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    Rp {{ number_format($periodSummary['summary']['total_service'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-300">
                                Base: Rp {{ number_format($periodSummary['summary']['subtotal'], 0, ',', '.') }}
                            </div>
                        </div>
                        
                        <div class="mt-3 pt-3 border-t dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Total Biaya Tambahan</span>
                                <span class="text-xl font-bold text-purple-600 dark:text-purple-400">
                                    Rp {{ number_format($periodSummary['summary']['total_tax'] + $periodSummary['summary']['total_service'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- TOP PRODUCTS --}}
        @if(count($topProducts) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🏆 Produk Terlaris</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">#</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Produk</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Kategori</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Qty</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Total</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $index => $product)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="py-3 px-4 text-gray-800 dark:text-white font-bold">{{ $index + 1 }}</td>
                                    <td class="py-3 px-4 text-gray-800 dark:text-white">{{ $product['name'] }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400 text-sm">{{ $product['category'] }}</td>
                                    <td class="py-3 px-4 text-right text-gray-800 dark:text-white font-semibold">{{ $product['quantity'] }}</td>
                                    <td class="py-3 px-4 text-right text-gray-800 dark:text-white">Rp {{ number_format($product['total'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">
                                            {{ $product['percentage'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- NO DATA MESSAGE --}}
        @if($reportType === 'daily' && !$dailySummary)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                <div class="text-yellow-800 dark:text-yellow-200 text-lg font-semibold mb-2">
                    📊 Tidak ada data untuk tanggal ini
                </div>
                <div class="text-yellow-700 dark:text-yellow-300 text-sm">
                    Silakan pilih tanggal lain atau pastikan ada transaksi pada tanggal yang dipilih.
                </div>
            </div>
        @endif

        @if($reportType === 'period' && !$periodSummary)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                <div class="text-yellow-800 dark:text-yellow-200 text-lg font-semibold mb-2">
                    📊 Tidak ada data untuk periode ini
                </div>
                <div class="text-yellow-700 dark:text-yellow-300 text-sm">
                    Silakan pilih periode lain atau pastikan ada transaksi pada periode yang dipilih.
                </div>
            </div>
        @endif

    </div>
    
    {{-- Charts JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            
            // Re-initialize charts on Livewire update
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', (message, component) => {
                    setTimeout(() => initCharts(), 100);
                });
            }
        });
        
        function initCharts() {
            console.log('🔄 Initializing charts...');
            
            // Sales Trend Chart
            @if(isset($salesTrendData) && count($salesTrendData['data']) > 0)
            console.log('📊 Sales Trend Data:', @json($salesTrendData));
            
            if (document.getElementById('salesTrendChart')) {
                // Destroy existing chart if any
                if (window.salesTrendChart && typeof window.salesTrendChart.destroy === 'function') {
                    window.salesTrendChart.destroy();
                }
                
                var salesOptions = {
                    series: [{
                        name: 'Penjualan',
                        data: @json($salesTrendData['data'])
                    }],
                    chart: {
                        type: 'area',
                        height: 300,
                        toolbar: { show: false },
                        zoom: { enabled: false }
                    },
                    dataLabels: { enabled: false },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    colors: ['#3B82F6'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.1,
                        }
                    },
                    xaxis: {
                        categories: @json($salesTrendData['labels']),
                        labels: {
                            style: { colors: '#9CA3AF' }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                            },
                            style: { colors: '#9CA3AF' }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                            }
                        }
                    },
                    grid: {
                        borderColor: '#374151',
                        strokeDashArray: 4,
                    }
                };
                
                window.salesTrendChart = new ApexCharts(document.getElementById('salesTrendChart'), salesOptions);
                window.salesTrendChart.render();
                console.log('✅ Sales chart rendered');
            }
            @else
            console.log('⚠️ No sales trend data available');
            @endif
            
            // Payment Method Chart
            @if(isset($paymentChartData) && count($paymentChartData['data']) > 0)
            console.log('💳 Payment Chart Data:', @json($paymentChartData));
            
            if (document.getElementById('paymentChart')) {
                // Destroy existing chart if any
                if (window.paymentChart && typeof window.paymentChart.destroy === 'function') {
                    window.paymentChart.destroy();
                }
                
                var paymentOptions = {
                    series: @json($paymentChartData['data']),
                    chart: {
                        type: 'donut',
                        height: 300
                    },
                    labels: @json($paymentChartData['labels']),
                    colors: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'],
                    legend: {
                        position: 'bottom',
                        labels: { colors: '#9CA3AF' }
                    },
                    dataLabels: {
                        formatter: function(val, opts) {
                            return val.toFixed(1) + '%';
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: function(w) {
                                            const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            return 'Rp ' + Math.round(total).toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                            }
                        }
                    }
                };
                
                window.paymentChart = new ApexCharts(document.getElementById('paymentChart'), paymentOptions);
                window.paymentChart.render();
                console.log('✅ Payment chart rendered');
            }
            @else
            console.log('⚠️ No payment chart data available');
            @endif
            
            console.log('✅ Chart initialization complete');
        }
    </script>
</x-filament-panels::page>
