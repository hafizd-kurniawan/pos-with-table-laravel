<x-filament-panels::page>
    <div class="space-y-6">
        
        {{-- REPORT TYPE SELECTOR WITH EXPORT BUTTONS --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('report.type.label') }}
                    </label>
                    <select wire:model.live="reportType" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="daily">{{ __('report.type.daily') }}</option>
                        <option value="period">{{ __('report.type.period') }}</option>
                    </select>
                </div>
                
                @if($reportType === 'daily')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('report.date.label') }}
                        </label>
                        <input type="date" wire:model.live="selectedDate"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="flex items-end">
                        <button wire:click="generateCache" 
                                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                            🔄 {{ __('report.actions.generate_cache') }}
                        </button>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('report.date.start_label') }}
                        </label>
                        <input type="date" wire:model.live="startDate"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('report.date.end_label') }}
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
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">📥 {{ __('report.export.title') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('report.export.description') }}</p>
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
                                        {{ __('report.export.pdf.title') }}
                                        <span wire:loading wire:target="exportPdf" class="ml-2 text-xs">
                                            <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </h4>
                                    <p class="text-xs text-red-700 dark:text-red-300 mb-2">
                                        {{ __('report.export.pdf.description') }}
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
                                        {{ __('report.export.excel.title') }}
                                        <span wire:loading wire:target="exportExcel" class="ml-2 text-xs">
                                            <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </h4>
                                    <p class="text-xs text-green-700 dark:text-green-300 mb-2">
                                        {{ __('report.export.excel.description') }}
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
                            <p class="font-semibold mb-1">💡 {{ __('report.export.tips.title') }}</p>
                            <ul class="space-y-1 ml-4 list-disc">
                                <li><strong>PDF</strong> - {{ __('report.export.tips.pdf') }}</li>
                                <li><strong>Excel</strong> - {{ __('report.export.tips.excel') }}</li>
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
                    <div class="text-sm opacity-90">{{ __('report.summary.total_orders') }}</div>
                    <div class="text-3xl font-bold mt-2">{{ $dailySummary['summary']['total_orders'] }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ __('report.summary.items_sold', ['count' => $dailySummary['summary']['total_items']]) }}</div>
                </div>

                {{-- Gross Sales --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #10B981, #059669);">
                    <div class="text-sm opacity-90">{{ __('report.summary.gross_sales') }}</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($dailySummary['summary']['gross_sales'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ __('report.summary.before_discount') }}</div>
                </div>

                {{-- Discount --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #F59E0B, #D97706);">
                    <div class="text-sm opacity-90">{{ __('report.summary.total_discount') }}</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($dailySummary['summary']['total_discount'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ __('report.summary.discount_given') }}</div>
                </div>

                {{-- Net Sales --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #8B5CF6, #7C3AED);">
                    <div class="text-sm opacity-90">{{ __('report.summary.net_sales') }}</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($dailySummary['summary']['net_sales'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ __('report.summary.final_amount') }}</div>
                </div>
            </div>

            {{-- COMPARISON WITH YESTERDAY --}}
            @if(isset($dailySummary['comparison']))
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg shadow-lg p-6 border-2 border-purple-200 dark:border-purple-700">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">📊</span>
                    <span>{{ __('report.comparison.title') }}</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Revenue Comparison --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 {{ $dailySummary['comparison']['changes']['revenue']['trend'] === 'up' ? 'border-green-500' : 'border-red-500' }}">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('report.summary.net_sales') }}</div>
                        <div class="flex items-center gap-2">
                            <div class="text-2xl font-bold {{ $dailySummary['comparison']['changes']['revenue']['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $dailySummary['comparison']['changes']['revenue']['trend'] === 'up' ? '↑' : '↓' }}
                                {{ abs($dailySummary['comparison']['changes']['revenue']['percentage']) }}%
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            {{ __('report.comparison.yesterday', ['amount' => 'Rp ' . number_format($dailySummary['comparison']['yesterday']['net_sales'], 0, ',', '.')]) }}
                        </div>
                    </div>

                    {{-- Orders Comparison --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 {{ $dailySummary['comparison']['changes']['orders']['trend'] === 'up' ? 'border-green-500' : 'border-red-500' }}">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('report.summary.total_orders') }}</div>
                        <div class="flex items-center gap-2">
                            <div class="text-2xl font-bold {{ $dailySummary['comparison']['changes']['orders']['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $dailySummary['comparison']['changes']['orders']['trend'] === 'up' ? '↑' : '↓' }}
                                {{ abs($dailySummary['comparison']['changes']['orders']['amount']) }} orders
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            {{ __('report.comparison.yesterday', ['amount' => $dailySummary['comparison']['yesterday']['total_orders'] . ' orders']) }}
                        </div>
                    </div>

                    {{-- Average Comparison --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 {{ $dailySummary['comparison']['changes']['average']['trend'] === 'up' ? 'border-green-500' : 'border-red-500' }}">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('report.summary.average_transaction') }}</div>
                        <div class="flex items-center gap-2">
                            <div class="text-2xl font-bold {{ $dailySummary['comparison']['changes']['average']['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $dailySummary['comparison']['changes']['average']['trend'] === 'up' ? '↑' : '↓' }}
                                {{ abs($dailySummary['comparison']['changes']['average']['percentage']) }}%
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            {{ __('report.comparison.yesterday', ['amount' => 'Rp ' . number_format($dailySummary['comparison']['yesterday']['average_transaction'], 0, ',', '.')]) }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- PEAK HOURS --}}
            @if(isset($dailySummary['peak_hours']) && $dailySummary['peak_hours']['busiest'])
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">🕐</span>
                    <span>{{ __('report.peak_hours.title') }}</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-2 border-green-200 dark:border-green-700">
                        <div class="flex items-center gap-3">
                            <div class="text-4xl">🔥</div>
                            <div class="flex-1">
                                <div class="text-sm text-green-700 dark:text-green-300 font-semibold">{{ __('report.peak_hours.busiest') }}</div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $dailySummary['peak_hours']['busiest']['hour'] }}</div>
                                <div class="text-sm text-green-600 dark:text-green-400">
                                    {{ $dailySummary['peak_hours']['busiest']['orders'] }} orders • Rp {{ number_format($dailySummary['peak_hours']['busiest']['revenue'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex items-center gap-3">
                            <div class="text-4xl">😴</div>
                            <div class="flex-1">
                                <div class="text-sm text-blue-700 dark:text-blue-300 font-semibold">{{ __('report.peak_hours.slowest') }}</div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $dailySummary['peak_hours']['slowest']['hour'] }}</div>
                                <div class="text-sm text-blue-600 dark:text-blue-400">
                                    {{ $dailySummary['peak_hours']['slowest']['orders'] }} orders • Rp {{ number_format($dailySummary['peak_hours']['slowest']['revenue'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                    <div class="flex items-start gap-2">
                        <span class="text-lg">💡</span>
                        <div class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>{{ __('report.peak_hours.recommendation') }}</strong> {{ __('report.peak_hours.staff_recommendation', ['hour' => $dailySummary['peak_hours']['busiest']['hour']]) }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- CUSTOMER INSIGHTS --}}
            @if(isset($dailySummary['customer_insights']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">👥</span>
                    <span>{{ __('report.customer_insights.title') }}</span>
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $dailySummary['customer_insights']['unique_customers'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('report.customer_insights.unique_customers') }}</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $dailySummary['customer_insights']['repeat_customers'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('report.customer_insights.repeat_customers', ['percentage' => $dailySummary['customer_insights']['repeat_percentage']]) }}</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $dailySummary['customer_insights']['new_customers'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('report.customer_insights.new_customers', ['percentage' => $dailySummary['customer_insights']['new_percentage']]) }}</div>
                    </div>
                    <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $dailySummary['customer_insights']['avg_items_per_order'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('report.customer_insights.avg_items') }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Revenue Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">{{ __('report.breakdown.revenue') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('report.breakdown.subtotal') }}</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['subtotal'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('report.breakdown.tax') }}</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['total_tax'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('report.breakdown.service_charge') }}</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['total_service'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('report.summary.average_transaction') }}</span>
                            <span class="font-semibold dark:text-white">Rp {{ number_format($dailySummary['summary']['average_transaction'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('report.summary.total_items') }}</span>
                            <span class="font-semibold dark:text-white">{{ $dailySummary['summary']['total_items'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('report.summary.total_customers') }}</span>
                            <span class="font-semibold dark:text-white">{{ $dailySummary['summary']['total_customers'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">{{ __('report.breakdown.payment_methods') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($dailySummary['payment_breakdown'] as $payment)
                        <div class="border dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase">{{ $payment['method'] }}</span>
                                <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">{{ $payment['percentage'] }}%</span>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                Rp {{ number_format($payment['amount'], 0, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                {{ $payment['count'] }} {{ __('report.breakdown.transaksi') ?? 'transaksi' }}
                            </div>
                            @if($payment['count'] > 0)
                            <div class="pt-2 border-t border-gray-200 dark:border-gray-600">
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    Avg: <span class="font-semibold text-purple-600 dark:text-purple-400">Rp {{ number_format($payment['amount'] / $payment['count'], 0, ',', '.') }}</span>/{{ __('report.summary.per_transaction') }}
                                </div>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- DISCOUNT & TAX BREAKDOWN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Discount Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💰 {{ __('report.breakdown.discounts') }}</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('report.breakdown.total_discount_given') }}</span>
                            <span class="text-xl font-bold text-orange-600 dark:text-orange-400">
                                Rp {{ number_format($dailySummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.summary.gross_sales') }}</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($dailySummary['summary']['gross_sales'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.breakdown.after_discount') }}</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($dailySummary['summary']['gross_sales'] - $dailySummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-3 pt-3 border-t dark:border-gray-700">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('report.breakdown.discount_percentage') }}</span>
                                <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full font-semibold">
                                    {{ $dailySummary['summary']['gross_sales'] > 0 ? number_format(($dailySummary['summary']['total_discount'] / $dailySummary['summary']['gross_sales']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Tax & Service Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🧾 {{ __('report.breakdown.tax_and_fees') }}</h3>
                    <div class="space-y-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('report.breakdown.tax') }}</span>
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
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ __('report.breakdown.service_charge') }}</span>
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
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('report.breakdown.total_fees') }}</span>
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
                    <div class="text-sm opacity-90">{{ __('report.summary.total_orders') }}</div>
                    <div class="text-3xl font-bold mt-2">{{ $periodSummary['summary']['total_orders'] }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ $periodSummary['period']['days'] }} {{ __('report.weekly_trend.day') }}</div>
                </div>

                {{-- Net Sales --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #10B981, #059669);">
                    <div class="text-sm opacity-90">{{ __('report.summary.net_sales') }}</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($periodSummary['summary']['net_sales'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ __('report.summary.total_items') }}</div>
                </div>

                {{-- Growth --}}
                @php
                    $isGrowthUp = $periodSummary['comparison']['growth']['trend'] === 'up';
                    $growthGradient = $isGrowthUp 
                        ? 'background: linear-gradient(to bottom right, #10B981, #059669);' 
                        : 'background: linear-gradient(to bottom right, #EF4444, #DC2626);';
                @endphp
                <div class="rounded-lg shadow-lg p-6 text-white" style="{{ $growthGradient }}">
                    <div class="text-sm opacity-90">{{ __('report.summary.growth') }}</div>
                    <div class="text-3xl font-bold mt-2">
                        {{ $isGrowthUp ? '↑' : '↓' }}
                        {{ abs($periodSummary['comparison']['growth']['percentage']) }}%
                    </div>
                    <div class="text-xs opacity-75 mt-1 uppercase">{{ $periodSummary['comparison']['growth']['status'] }}</div>
                </div>

                {{-- Average Transaction --}}
                <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, #8B5CF6, #7C3AED);">
                    <div class="text-sm opacity-90">{{ __('report.summary.average_transaction') }}</div>
                    <div class="text-2xl font-bold mt-2">Rp {{ number_format($periodSummary['summary']['average_transaction'], 0, ',', '.') }}</div>
                    <div class="text-xs opacity-75 mt-1">{{ __('report.summary.per_transaction') }}</div>
                </div>
            </div>

            {{-- Comparison --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">{{ __('report.comparison.period_title') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('report.comparison.current_period') }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mb-1">
                            {{ \Carbon\Carbon::parse($periodSummary['period']['start'])->format('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($periodSummary['period']['end'])->format('d M Y') }}
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($periodSummary['summary']['net_sales'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('report.comparison.previous_period') }}</div>
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
                        <span class="text-gray-600 dark:text-gray-400">{{ __('report.comparison.difference') }}</span>
                        <span class="text-xl font-bold {{ $periodSummary['comparison']['growth']['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $periodSummary['comparison']['growth']['trend'] === 'up' ? '+' : '' }}
                            Rp {{ number_format($periodSummary['comparison']['growth']['amount'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Payment Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">{{ __('report.breakdown.payment_methods') }}</h3>
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
                                {{ $payment['count'] }} {{ __('report.breakdown.transaksi') ?? 'transaksi' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- DISCOUNT & TAX BREAKDOWN FOR PERIOD --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Discount Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💰 {{ __('report.breakdown.discounts') }}</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('report.breakdown.total_discount_given') }}</span>
                            <span class="text-xl font-bold text-orange-600 dark:text-orange-400">
                                Rp {{ number_format($periodSummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.summary.gross_sales') }}</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($periodSummary['summary']['gross_sales'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.breakdown.after_discount') }}</span>
                            <span class="text-sm font-semibold dark:text-white">
                                Rp {{ number_format($periodSummary['summary']['gross_sales'] - $periodSummary['summary']['total_discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-3 pt-3 border-t dark:border-gray-700">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('report.breakdown.discount_percentage') }}</span>
                                <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full font-semibold">
                                    {{ $periodSummary['summary']['gross_sales'] > 0 ? number_format(($periodSummary['summary']['total_discount'] / $periodSummary['summary']['gross_sales']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Tax & Service Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🧾 {{ __('report.breakdown.tax_and_fees') }}</h3>
                    <div class="space-y-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('report.breakdown.tax') }}</span>
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
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ __('report.breakdown.service_charge') }}</span>
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
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('report.breakdown.total_fees') }}</span>
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
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🏆 {{ __('report.top_products.title') }}</h3>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">#</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('report.top_products.product') }}</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('report.top_products.category') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('report.top_products.qty') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('report.top_products.total') }}</th>
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

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @foreach($topProducts as $index => $product)
                        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $product['name'] }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $product['category'] }}</div>
                                    </div>
                                </div>
                                <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">
                                    {{ $product['percentage'] }}%
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-3 pt-2 border-t dark:border-gray-700">
                                 <div class="text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Qty:</span>
                                    <span class="font-semibold text-gray-800 dark:text-white">{{ $product['quantity'] }}</span>
                                 </div>
                                 <div class="font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($product['total'], 0, ',', '.') }}
                                 </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- WEEKLY TREND --}}
        @if($reportType === 'daily' && isset($dailySummary['weekly_trend']) && !empty($dailySummary['weekly_trend']['days']))
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">📈</span>
                    {{ __('report.weekly_trend.title') }}
                </h3>
                
                {{-- Week Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-indigo-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.total_revenue') }}</div>
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                            Rp {{ number_format($dailySummary['weekly_trend']['summary']['total_revenue'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-purple-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.average_per_day') }}</div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            Rp {{ number_format($dailySummary['weekly_trend']['summary']['average_per_day'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-green-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.best_day') }}</div>
                        <div class="text-xl font-bold text-green-600 dark:text-green-400">
                            {{ $dailySummary['weekly_trend']['summary']['best_day']['day_short'] }} 
                            (Rp {{ number_format($dailySummary['weekly_trend']['summary']['best_day']['revenue'] / 1000, 0, ',', '.') }}K)
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-blue-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.growth') }}</div>
                        <div class="text-2xl font-bold {{ $dailySummary['weekly_trend']['growth']['trend'] === 'up' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $dailySummary['weekly_trend']['growth']['trend'] === 'up' ? '↑' : '↓' }} 
                            {{ abs($dailySummary['weekly_trend']['growth']['percentage']) }}%
                        </div>
                    </div>
                </div>
                
                {{-- Daily Breakdown Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-indigo-600 dark:bg-indigo-800 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('report.weekly_trend.day') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('report.weekly_trend.date') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.weekly_trend.orders') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.weekly_trend.revenue') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dailySummary['weekly_trend']['days'] as $day)
                                <tr class="hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors
                                    @if($day['revenue'] == $dailySummary['weekly_trend']['summary']['best_day']['revenue']) bg-green-50 dark:bg-green-900/20 @endif">
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                                        {{ $day['day_short'] }}
                                        @if($day['revenue'] == $dailySummary['weekly_trend']['summary']['best_day']['revenue']) 🏆 @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 dark:text-white">{{ $day['orders'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-white">Rp {{ number_format($day['revenue'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="grid grid-cols-1 gap-4 md:hidden p-4">
                        @foreach($dailySummary['weekly_trend']['days'] as $day)
                            <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm
                                @if($day['revenue'] == $dailySummary['weekly_trend']['summary']['best_day']['revenue']) border-green-500 ring-1 ring-green-500 @endif">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $day['day_short'] }}
                                        @if($day['revenue'] == $dailySummary['weekly_trend']['summary']['best_day']['revenue']) 🏆 @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</div>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                     <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $day['orders'] }} Orders
                                     </div>
                                     <div class="font-bold text-gray-900 dark:text-white">
                                        Rp {{ number_format($day['revenue'], 0, ',', '.') }}
                                     </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- STOCK ALERTS --}}
        @if($reportType === 'daily' && isset($dailySummary['stock_alerts']) && !empty($dailySummary['stock_alerts']['alerts']))
            <div class="bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">⚠️</span>
                    {{ __('report.stock_alerts.title') }}
                </h3>
                
                {{-- Alert Summary --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 rounded-lg p-4">
                        <div class="text-sm text-red-700 dark:text-red-300 font-medium">🔴 {{ __('report.stock_alerts.critical') }}</div>
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $dailySummary['stock_alerts']['summary']['critical_count'] }}</div>
                        <div class="text-xs text-red-600 dark:text-red-400">{{ __('report.stock_alerts.reorder_now') }}</div>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 rounded-lg p-4">
                        <div class="text-sm text-yellow-700 dark:text-yellow-300 font-medium">🟡 {{ __('report.stock_alerts.warning') }}</div>
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $dailySummary['stock_alerts']['summary']['warning_count'] }}</div>
                        <div class="text-xs text-yellow-600 dark:text-yellow-400">{{ __('report.stock_alerts.monitor_closely') }}</div>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/30 border-l-4 border-blue-500 rounded-lg p-4">
                        <div class="text-sm text-blue-700 dark:text-blue-300 font-medium">🔵 {{ __('report.stock_alerts.watch') }}</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $dailySummary['stock_alerts']['summary']['watch_count'] }}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-400">{{ __('report.stock_alerts.prepare_reorder') }}</div>
                    </div>
                </div>
                
                {{-- Alert Details --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-red-600 dark:bg-red-800 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('report.stock_alerts.product') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('report.stock_alerts.stock') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('report.stock_alerts.sold_today') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('report.stock_alerts.out_in') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('report.stock_alerts.recommendation') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dailySummary['stock_alerts']['alerts'] as $alert)
                                <tr class="hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                                        @if($alert['alert_level'] === 'critical') 🔴 @endif
                                        @if($alert['alert_level'] === 'warning') 🟡 @endif
                                        @if($alert['alert_level'] === 'watch') 🔵 @endif
                                        {{ $alert['product_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold
                                        @if($alert['alert_level'] === 'critical') text-red-600 dark:text-red-400 @endif
                                        @if($alert['alert_level'] === 'warning') text-yellow-600 dark:text-yellow-400 @endif
                                        @if($alert['alert_level'] === 'watch') text-blue-600 dark:text-blue-400 @endif">
                                        {{ $alert['current_stock'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-800 dark:text-white">{{ $alert['sold_today'] }}</td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        @if($alert['days_until_stockout'] <= 2)
                                            <span class="text-red-600 dark:text-red-400 font-bold">{{ $alert['days_until_stockout'] }} hari ⚠️</span>
                                        @else
                                            <span class="text-gray-600 dark:text-gray-400">{{ $alert['days_until_stockout'] }} hari</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded
                                            @if($alert['recommendation'] === 'Reorder NOW!') bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 @else bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 @endif">
                                            {{ $alert['recommendation'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="grid grid-cols-1 gap-4 md:hidden p-4">
                        @foreach($dailySummary['stock_alerts']['alerts'] as $alert)
                            <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-medium text-gray-800 dark:text-white">
                                        @if($alert['alert_level'] === 'critical') 🔴 @endif
                                        @if($alert['alert_level'] === 'warning') 🟡 @endif
                                        @if($alert['alert_level'] === 'watch') 🔵 @endif
                                        {{ $alert['product_name'] }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-center text-sm mt-3 border-t dark:border-gray-700 pt-2">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Stock</div>
                                        <div class="font-bold
                                            @if($alert['alert_level'] === 'critical') text-red-600 dark:text-red-400 @endif
                                            @if($alert['alert_level'] === 'warning') text-yellow-600 dark:text-yellow-400 @endif
                                            @if($alert['alert_level'] === 'watch') text-blue-600 dark:text-blue-400 @endif">
                                            {{ $alert['current_stock'] }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Sold</div>
                                        <div class="font-semibold text-gray-800 dark:text-white">{{ $alert['sold_today'] }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Out In</div>
                                        <div class="font-semibold text-gray-800 dark:text-white">{{ $alert['days_until_stockout'] }}d</div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 italic bg-gray-50 dark:bg-gray-700/50 p-2 rounded">
                                    {{ $alert['recommendation'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- STAFF PERFORMANCE --}}
        @if($reportType === 'daily' && isset($dailySummary['staff_performance']) && !empty($dailySummary['staff_performance']['staff']))
            <div class="bg-gradient-to-br from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">👨‍💼</span>
                    {{ __('report.staff_performance.title') }}
                </h3>
                
                {{-- Performance Summary --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-green-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.staff_performance.total_staff') }}</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $dailySummary['staff_performance']['summary']['total_staff'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-teal-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.staff_performance.avg_orders') }}</div>
                        <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $dailySummary['staff_performance']['summary']['avg_orders_per_staff'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-blue-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.staff_performance.avg_revenue') }}</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            Rp {{ number_format($dailySummary['staff_performance']['summary']['avg_revenue_per_staff'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                
                {{-- Staff Leaderboard --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-green-600 dark:bg-green-800 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('report.staff_performance.rank') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('report.staff_performance.name') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.staff_performance.orders') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.staff_performance.revenue') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('report.staff_performance.performance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($dailySummary['staff_performance']['staff'] as $staff)
                                <tr class="hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors
                                    @if($staff['badge'] === 'top_performer') bg-yellow-50 dark:bg-yellow-900/20 @endif">
                                    <td class="px-4 py-3 font-bold text-lg text-gray-800 dark:text-white">
                                        @if($staff['rank'] === 1) 🏆
                                        @elseif($staff['rank'] === 2) 🥈
                                        @elseif($staff['rank'] === 3) 🥉
                                        @else {{ $staff['rank'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $staff['user_name'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-white">{{ $staff['total_orders'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-white">Rp {{ number_format($staff['total_revenue'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs font-medium rounded
                                            @if($staff['badge'] === 'top_performer') bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300
                                            @elseif($staff['badge'] === 'above_average') bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300
                                            @else bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                            @endif">
                                            @if($staff['performance_vs_avg'] > 0) +@endif{{ $staff['performance_vs_avg'] }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="grid grid-cols-1 gap-4 md:hidden p-4">
                        @foreach($dailySummary['staff_performance']['staff'] as $staff)
                            <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm
                                @if($staff['badge'] === 'top_performer') border-yellow-400 ring-1 ring-yellow-400 @endif">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">
                                            @if($staff['rank'] === 1) 🏆
                                            @elseif($staff['rank'] === 2) 🥈
                                            @elseif($staff['rank'] === 3) 🥉
                                            @else #{{ $staff['rank'] }}
                                            @endif
                                        </span>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $staff['user_name'] }}</div>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded
                                        @if($staff['badge'] === 'top_performer') bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300
                                        @elseif($staff['badge'] === 'above_average') bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                        @endif">
                                        @if($staff['performance_vs_avg'] > 0) +@endif{{ $staff['performance_vs_avg'] }}%
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm mt-2 border-t dark:border-gray-700 pt-2">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Orders</div>
                                        <div class="font-semibold text-gray-800 dark:text-white">{{ $staff['total_orders'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Revenue</div>
                                        <div class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($staff['total_revenue'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- PROFIT ANALYSIS --}}
        @if($reportType === 'daily' && isset($dailySummary['profit_analysis']))
            <div class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">💰</span>
                    {{ __('report.profit_analysis.title') }}
                </h3>
                
                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-blue-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.gross_revenue') }}</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            Rp {{ number_format($dailySummary['profit_analysis']['summary']['gross_revenue'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-red-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.total_cogs') }}</div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            Rp {{ number_format($dailySummary['profit_analysis']['summary']['total_cogs'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-green-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.net_profit') }}</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            Rp {{ number_format($dailySummary['profit_analysis']['summary']['net_profit'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-yellow-500">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.profit_margin') }}</div>
                        <div class="text-2xl font-bold {{ $dailySummary['profit_analysis']['summary']['profit_margin'] >= $dailySummary['profit_analysis']['summary']['target_margin'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $dailySummary['profit_analysis']['summary']['profit_margin'] }}%
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('report.profit_analysis.target') }}: {{ $dailySummary['profit_analysis']['summary']['target_margin'] }}%
                            @if($dailySummary['profit_analysis']['summary']['margin_difference'] >= 0)
                                <span class="text-green-600 dark:text-green-400">(+{{ $dailySummary['profit_analysis']['summary']['margin_difference'] }}%)</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">({{ $dailySummary['profit_analysis']['summary']['margin_difference'] }}%)</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Recommendations --}}
                @if(!empty($dailySummary['profit_analysis']['recommendations']))
                <div class="mb-4 space-y-2">
                    @foreach($dailySummary['profit_analysis']['recommendations'] as $rec)
                    <div class="p-3 rounded-lg {{ $rec['type'] === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700' : 'bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700' }}">
                        <div class="flex items-start gap-2">
                            <span class="text-xl">{{ $rec['icon'] }}</span>
                            <div class="flex-1">
                                <div class="font-medium text-gray-800 dark:text-white">{{ $rec['message'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">→ {{ $rec['action'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                {{-- Product Profitability Table --}}
                @if(!empty($dailySummary['profit_analysis']['products']))
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-yellow-600 dark:bg-yellow-800">
                        <h4 class="font-bold text-white">{{ __('report.profit_analysis.product_profitability') }}</h4>
                    </div>
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-yellow-100 dark:bg-yellow-900/30 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('report.profit_analysis.product') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('report.profit_analysis.qty') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.profit_analysis.revenue') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.profit_analysis.cogs') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('report.profit_analysis.profit') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('report.profit_analysis.margin') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach(array_slice($dailySummary['profit_analysis']['products'], 0, 10) as $product)
                                <tr class="hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $product['product_name'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-800 dark:text-white">{{ $product['quantity_sold'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 dark:text-white">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">Rp {{ number_format($product['cogs'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">Rp {{ number_format($product['profit'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs font-medium rounded
                                            @if($product['margin'] >= 50) bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300
                                            @elseif($product['margin'] >= 30) bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300
                                            @elseif($product['margin'] >= 20) bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300
                                            @else bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300
                                            @endif">
                                            {{ $product['margin'] }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="grid grid-cols-1 gap-4 md:hidden p-4">
                        @foreach(array_slice($dailySummary['profit_analysis']['products'], 0, 10) as $product)
                            <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $product['product_name'] }}</div>
                                    <span class="px-2 py-1 text-xs font-medium rounded
                                        @if($product['margin'] >= 50) bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300
                                        @elseif($product['margin'] >= 30) bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300
                                        @elseif($product['margin'] >= 20) bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300
                                        @else bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300
                                        @endif">
                                        {{ $product['margin'] }}%
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Sold</div>
                                        <div class="font-semibold text-gray-800 dark:text-white">{{ $product['quantity_sold'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Profit</div>
                                        <div class="font-bold text-green-600 dark:text-green-400">Rp {{ number_format($product['profit'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 flex justify-between border-t dark:border-gray-700 pt-1">
                                    <span>Rev: Rp {{ number_format($product['revenue'], 0, ',', '.') }}</span>
                                    <span>COGS: Rp {{ number_format($product['cogs'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        @endif

        {{-- NO DATA MESSAGE --}}
        @if($reportType === 'daily' && !$dailySummary)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                <div class="text-yellow-800 dark:text-yellow-200 text-lg font-semibold mb-2">
                    📊 {{ __('report.notifications.no_data') }}
                </div>
                <div class="text-yellow-700 dark:text-yellow-300 text-sm">
                    {{ __('report.notifications.no_data_daily') }}
                </div>
            </div>
        @endif


        {{-- NEW: Top Products for Period --}}
        @if(isset($topProducts) && count($topProducts) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🏆 {{ __('report.top_products.title') }}</h3>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ __('report.top_products.product') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">{{ __('report.top_products.qty') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">{{ __('report.top_products.total') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $index => $product)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index == 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }} font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold">{{ $product['name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($product['quantity'], 0) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-green-600">
                                    Rp {{ number_format($product['total'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                        {{ $product['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @foreach($topProducts as $index => $product)
                        <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full {{ $index == 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }} text-xs font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $product['name'] }}</div>
                                </div>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                    {{ $product['percentage'] }}%
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-3 pt-2 border-t dark:border-gray-700">
                                 <div class="text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Qty:</span>
                                    <span class="font-semibold text-gray-800 dark:text-white">{{ number_format($product['quantity'], 0) }}</span>
                                 </div>
                                 <div class="font-bold text-green-600">
                                    Rp {{ number_format($product['total'], 0, ',', '.') }}
                                 </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- NEW: Daily Trend Chart --}}
        @if(isset($periodSummary['daily_trend']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📈 {{ __('report.weekly_trend.daily_trend_title') }}</h3>
            <canvas id="periodTrendChart" height="80"></canvas>
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.best_day') }}</div>
                    <div class="text-lg font-bold text-green-600">
                        {{ $periodSummary['daily_trend']['best_day']['date'] }}
                    </div>
                    <div class="text-xs">Rp {{ number_format($periodSummary['daily_trend']['best_day']['amount'], 0, ',', '.') }}</div>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.average_per_day') }}</div>
                    <div class="text-lg font-bold text-blue-600">
                        Rp {{ number_format($periodSummary['daily_trend']['average'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded">
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('report.weekly_trend.worst_day') }}</div>
                    <div class="text-lg font-bold text-orange-600">
                        {{ $periodSummary['daily_trend']['worst_day']['date'] }}
                    </div>
                    <div class="text-xs">Rp {{ number_format($periodSummary['daily_trend']['worst_day']['amount'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- NEW: Profit Analysis --}}
        @if(isset($periodSummary['profit_analysis']))
        <div class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg shadow p-6 border border-yellow-200 dark:border-yellow-800 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💰 {{ __('report.profit_analysis.title') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.revenue') }}</div>
                    <div class="text-2xl font-bold text-blue-600">
                        Rp {{ number_format($periodSummary['profit_analysis']['total_revenue'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.cogs') }}</div>
                    <div class="text-2xl font-bold text-red-600">
                        Rp {{ number_format($periodSummary['profit_analysis']['total_cogs'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.net_profit') }}</div>
                    <div class="text-2xl font-bold text-green-600">
                        Rp {{ number_format($periodSummary['profit_analysis']['net_profit'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('report.profit_analysis.margin') }}</div>
                    <div class="text-2xl font-bold {{ $periodSummary['profit_analysis']['margin_percentage'] >= 35 ? 'text-green-600' : 'text-orange-600' }}">
                        {{ number_format($periodSummary['profit_analysis']['margin_percentage'], 1) }}%
                    </div>
                </div>
            </div>
            
            @if(isset($periodSummary['profit_analysis']['recommendations']) && count($periodSummary['profit_analysis']['recommendations']) > 0)
            <div class="space-y-2">
                @foreach($periodSummary['profit_analysis']['recommendations'] as $rec)
                <div class="bg-white dark:bg-gray-800 rounded p-3 flex items-start gap-2">
                    <span class="text-xl">{{ $rec['icon'] }}</span>
                    <div class="flex-1">
                        <strong>{{ $rec['message'] }}</strong>
                        <div class="text-sm text-gray-600">→ {{ $rec['action'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- NEW: Staff Performance --}}
        @if(isset($periodSummary['staff_performance']) && count($periodSummary['staff_performance']['staff']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">👨‍💼 {{ __('report.staff_performance.title') }}</h3>
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ __('report.staff_performance.rank') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ __('report.staff_performance.name') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase">{{ __('report.staff_performance.orders') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase">{{ __('report.top_products.total') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periodSummary['staff_performance']['staff'] as $staff)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $staff['rank'] == 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }} font-bold">
                                    {{ $staff['rank'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $staff['name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $staff['total_orders'] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">
                                Rp {{ number_format($staff['total_sales'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                Rp {{ number_format($staff['average_transaction'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View --}}
            <div class="grid grid-cols-1 gap-4 md:hidden">
                @foreach($periodSummary['staff_performance']['staff'] as $staff)
                    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full {{ $staff['rank'] == 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }} text-xs font-bold">
                                    {{ $staff['rank'] }}
                                </span>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $staff['name'] }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm mt-2 border-t dark:border-gray-700 pt-2">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Orders</div>
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $staff['total_orders'] }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Avg</div>
                                <div class="font-semibold text-gray-800 dark:text-white">Rp {{ number_format($staff['average_transaction'], 0, ',', '.') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
                                <div class="font-bold text-green-600">Rp {{ number_format($staff['total_sales'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- NEW: Customer Insights --}}
        @if(isset($periodSummary['customer_insights']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">👥 {{ __('report.customer_insights.title') }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">
                        {{ number_format($periodSummary['customer_insights']['total_customers'], 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('report.customer_insights.total_customers') }}</div>
                    <div class="text-xs {{ $periodSummary['customer_insights']['growth']['trend'] == 'up' ? 'text-green-600' : 'text-red-600' }} mt-2">
                        {{ $periodSummary['customer_insights']['growth']['trend'] == 'up' ? '↗️' : '↘️' }}
                        {{ abs($periodSummary['customer_insights']['growth']['percentage']) }}% vs previous
                    </div>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">
                        Rp {{ number_format($periodSummary['customer_insights']['average_spend'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('report.customer_insights.average_spend') }}</div>
                </div>
            </div>
            
            @if(count($periodSummary['customer_insights']['top_customers']) > 0)
            <h4 class="font-semibold mb-2">{{ __('report.customer_insights.top_customers') }}</h4>
            <div class="space-y-2">
                @foreach($periodSummary['customer_insights']['top_customers'] as $customer)
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                    <div>
                        <div class="font-semibold">{{ $customer['name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $customer['total_orders'] }} orders</div>
                    </div>
                    <div class="text-right font-bold text-green-600">
                        Rp {{ number_format($customer['total_spent'], 0, ',', '.') }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        @push('scripts')
        <script>
        document.addEventListener('livewire:load', function() {
            @if(isset($periodSummary['daily_trend']))
            const ctx = document.getElementById('periodTrendChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($periodSummary['daily_trend']['labels']),
                        datasets: [{
                            label: '{{ __('report.widgets.sales_chart.sales_label') }}',
                            data: @json($periodSummary['daily_trend']['sales']),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: true }
                        }
                    }
                });
            }
            @endif
        });
        </script>
        @endpush
        @if($reportType === 'period' && !$periodSummary)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                <div class="text-yellow-800 dark:text-yellow-200 text-lg font-semibold mb-2">
                    📊 {{ __('report.notifications.no_data') }}
                </div>
                <div class="text-yellow-700 dark:text-yellow-300 text-sm">
                    {{ __('report.notifications.no_data_period') }}
                </div>
            </div>
        @endif

</x-filament-panels::page>
