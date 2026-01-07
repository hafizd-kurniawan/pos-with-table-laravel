<x-filament-panels::page>
    {{-- EXPORT BUTTONS - EXCEL ONLY (WORKING) --}}
    <div style="margin-bottom: 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 8px; color: white;">📊 {{ __('report.inventory.export.complete.title') }}</h3>
        <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin-bottom: 16px;">
            {!! __('report.inventory.export.complete.desc') !!}
        </p>
        
        <button wire:click="exportAllExcel" 
            style="display: inline-block; padding: 16px 32px; background-color: white; color: #059669; border: none; border-radius: 10px; font-weight: bold; font-size: 18px; cursor: pointer; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); transition: all 0.3s;">
            📊 {{ __('report.inventory.export.complete.button') }}
        </button>
        
        <div style="margin-top: 12px; padding: 8px 12px; background: rgba(255,255,255,0.2); border-radius: 6px; font-size: 12px; color: white;">
            💡 {{ __('report.inventory.export.complete.tip') }}
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">{{ __('report.inventory.stats.total_ingredients') }}</div>
            <div class="text-2xl font-bold text-gray-900">{{ $this->getTotalIngredients() }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">{{ __('report.inventory.stats.total_stock_value') }}</div>
            <div class="text-2xl font-bold text-green-600">Rp {{ number_format($this->getTotalStockValue(), 0, ',', '.') }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">{{ __('report.inventory.stats.low_stock_items') }}</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $this->getLowStockCount() }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">{{ __('report.inventory.stats.out_of_stock') }}</div>
            <div class="text-2xl font-bold text-red-600">{{ $this->getOutOfStockCount() }}</div>
        </x-filament::card>
    </div>

    {{-- Tabs and Content --}}
    <div x-data="{ activeTab: @entangle('activeTab').defer }" x-init="if(!activeTab) activeTab = 'stock-summary'">
        {{-- Tabs Navigation --}}
        <div class="mb-6">
            <div class="border-b border-gray-200 bg-white rounded-t-lg overflow-x-auto">
                <nav class="-mb-px flex space-x-8 px-4 min-w-max">
                    <button @click="activeTab = 'stock-summary'" 
                        :class="activeTab === 'stock-summary' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📊 {{ __('report.inventory.tabs.stock_summary') }}
                    </button>
                    
                    <button @click="activeTab = 'stock-movements'" 
                        :class="activeTab === 'stock-movements' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📦 {{ __('report.inventory.tabs.stock_movements') }}
                    </button>
                    
                    <button @click="activeTab = 'purchase-orders'" 
                        :class="activeTab === 'purchase-orders' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📋 {{ __('report.inventory.tabs.purchase_orders') }}
                    </button>
                    
                    <button @click="activeTab = 'inventory-value'" 
                        :class="activeTab === 'inventory-value' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        💰 {{ __('report.inventory.tabs.inventory_value') }}
                    </button>
                    
                    <button @click="activeTab = 'low-stock'" 
                        :class="activeTab === 'low-stock' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        ⚠️ {{ __('report.inventory.tabs.low_stock_alert') }}
                    </button>
                    
                    <button @click="activeTab = 'variance-analysis'" 
                        :class="activeTab === 'variance-analysis' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📉 Variance Analysis
                    </button>
                </nav>
            </div>
        </div>

        {{-- Content --}}
        <div class="mt-6">
        <div x-show="activeTab === 'stock-summary'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4 pb-3 border-b flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">📊 {{ __('report.inventory.stock_summary.heading') }}</h2>
                        <p class="text-sm text-gray-600 mt-1">{{ __('report.inventory.stock_summary.description') }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="exportStockSummaryExcel" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-md hover:shadow-lg transition-all duration-200 text-sm font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21,10.12H14.22L16.96,7.3C14.23,4.6 9.81,4.5 7.08,7.2C4.35,9.91 4.35,14.28 7.08,17C9.81,19.7 14.23,19.7 16.96,17C18.32,15.65 19,14.08 19,12.1H21C21,14.08 20.12,16.65 18.36,18.39C14.85,21.87 9.15,21.87 5.64,18.39C2.14,14.92 2.11,9.28 5.62,5.81C9.13,2.34 14.76,2.34 18.27,5.81L21,3V10.12M12.5,8V12.25L16,14.33L15.28,15.54L11,13V8H12.5Z"></path>
                            </svg>
                            <span>Excel</span>
                        </button>
                        <button wire:click="exportStockSummaryPdf" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 shadow-md hover:shadow-lg transition-all duration-200 text-sm font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,19L12,15H9V10H15V15L13,19H10Z"></path>
                            </svg>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">{{ __('report.inventory.stock_summary.table.sku') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.stock_summary.table.ingredient') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.stock_summary.table.category') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_summary.table.stock') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_summary.table.min_stock') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_summary.table.value') }}</th>
                                <th class="text-center p-2">{{ __('report.inventory.stock_summary.table.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockSummary as $item)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                            {{ $item['sku'] }}
                                        </span>
                                    </td>
                                    <td class="p-2 font-medium">{{ $item['name'] }}</td>
                                    <td class="p-2 text-sm text-gray-600">{{ $item['category'] }}</td>
                                    <td class="p-2 text-right">
                                        {{ $this->formatStock($item['current_stock']) }} {{ $item['unit'] }}
                                    </td>
                                    <td class="p-2 text-right text-sm text-gray-600">
                                        {{ $this->formatStock($item['min_stock']) }} {{ $item['unit'] }}
                                    </td>
                                    <td class="p-2 text-right font-medium">
                                        Rp {{ number_format($item['stock_value'], 0, ',', '.') }}
                                    </td>
                                    <td class="p-2 text-center">
                                        @if($item['status'] === 'safe')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">✅ Safe</span>
                                        @elseif($item['status'] === 'low')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">⚠️ Low</span>
                                        @elseif($item['status'] === 'critical')
                                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs font-medium">🔴 Critical</span>
                                        @elseif($item['status'] === 'out_of_stock')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">❌ Out</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ ucfirst($item['status']) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center p-4 text-gray-500">
                                        No data available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @forelse($stockSummary as $item)
                        <div class="bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-gray-900">{{ $item['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $item['sku'] }} • {{ $item['category'] }}</div>
                                </div>
                                <div>
                                    @if($item['status'] === 'safe')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">Safe</span>
                                    @elseif($item['status'] === 'low')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">Low</span>
                                    @elseif($item['status'] === 'critical')
                                        <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs font-medium">Critical</span>
                                    @elseif($item['status'] === 'out_of_stock')
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">Out</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ ucfirst($item['status']) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm mt-3 border-t pt-2">
                                <div>
                                    <div class="text-gray-500 text-xs">Stock</div>
                                    <div class="font-medium">{{ $this->formatStock($item['current_stock']) }} {{ $item['unit'] }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-gray-500 text-xs">Value</div>
                                    <div class="font-medium">Rp {{ number_format($item['stock_value'], 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-500 text-xs">Min Stock</div>
                                    <div class="font-medium">{{ $this->formatStock($item['min_stock']) }} {{ $item['unit'] }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-gray-500">No data available</div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>

        <div x-show="activeTab === 'low-stock'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-red-600">⚠️ {{ __('report.inventory.low_stock.heading') }}</h3>
                    <p class="text-sm text-gray-600">{{ __('report.inventory.low_stock.description') }}</p>
                </div>
                
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">{{ __('report.inventory.stock_summary.table.ingredient') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_summary.table.stock') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_summary.table.min_stock') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.low_stock.table.shortage') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                                <tr class="border-b hover:bg-red-50">
                                    <td class="p-2 font-medium">{{ $item['name'] }}</td>
                                    <td class="p-2 text-right text-red-600">
                                        {{ $this->formatStock($item['current_stock']) }} {{ $item['unit'] }}
                                    </td>
                                    <td class="p-2 text-right">
                                        {{ $this->formatStock($item['min_stock']) }} {{ $item['unit'] }}
                                    </td>
                                    <td class="p-2 text-right font-bold text-red-600">
                                        {{ $this->formatStock($item['shortage']) }} {{ $item['unit'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center p-4 text-green-600">
                                        ✅ {{ __('report.inventory.low_stock.all_good') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @forelse($lowStockItems as $item)
                        <div class="bg-white border border-red-200 rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div class="font-bold text-gray-900">{{ $item['name'] }}</div>
                                <div class="text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded">Shortage: {{ $this->formatStock($item['shortage']) }}</div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm mt-3 border-t pt-2">
                                <div>
                                    <div class="text-gray-500 text-xs">Current Stock</div>
                                    <div class="font-medium text-red-600">{{ $this->formatStock($item['current_stock']) }} {{ $item['unit'] }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-gray-500 text-xs">Min Stock</div>
                                    <div class="font-medium">{{ $this->formatStock($item['min_stock']) }} {{ $item['unit'] }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-green-600">
                            ✅ {{ __('report.inventory.low_stock.all_good') }}
                        </div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>
        
        <div x-show="activeTab === 'stock-movements'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4 pb-3 border-b flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">📦 {{ __('report.inventory.stock_movements.heading') }}</h2>
                        <p class="text-sm text-gray-600 mt-1">{{ __('report.inventory.stock_movements.description') }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="exportStockMovementsExcel" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-md hover:shadow-lg transition-all duration-200 text-sm font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21,10.12H14.22L16.96,7.3C14.23,4.6 9.81,4.5 7.08,7.2C4.35,9.91 4.35,14.28 7.08,17C9.81,19.7 14.23,19.7 16.96,17C18.32,15.65 19,14.08 19,12.1H21C21,14.08 20.12,16.65 18.36,18.39C14.85,21.87 9.15,21.87 5.64,18.39C2.14,14.92 2.11,9.28 5.62,5.81C9.13,2.34 14.76,2.34 18.27,5.81L21,3V10.12M12.5,8V12.25L16,14.33L15.28,15.54L11,13V8H12.5Z"></path>
                            </svg>
                            <span>Excel</span>
                        </button>
                        <button wire:click="exportStockMovementsPdf" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 shadow-md hover:shadow-lg transition-all duration-200 text-sm font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,19L12,15H9V10H15V15L13,19H10Z"></path>
                            </svg>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>
                {{-- Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('report.inventory.stock_movements.filters.start_date') }}</label>
                        <input type="date" wire:model="startDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('report.inventory.stock_movements.filters.end_date') }}</label>
                        <input type="date" wire:model="endDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('report.inventory.stock_movements.filters.type') }}</label>
                        <select wire:model="movementType" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">{{ __('report.inventory.stock_movements.filters.all_types') }}</option>
                            <option value="in">In (Masuk)</option>
                            <option value="out">Out (Keluar)</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="loadStockMovements" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            {{ __('report.inventory.stock_movements.filters.apply') }}
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">{{ __('report.inventory.stock_movements.table.date') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.stock_summary.table.ingredient') }}</th>
                                <th class="text-center p-2">{{ __('report.inventory.stock_movements.table.type') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_movements.table.quantity') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_movements.table.before') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.stock_movements.table.after') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.stock_movements.table.reference') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.stock_movements.table.user') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.stock_movements.table.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockMovements as $movement)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2 text-sm">{{ $movement['date'] }}</td>
                                    <td class="p-2 font-medium">{{ $movement['ingredient'] }}</td>
                                    <td class="p-2 text-center">
                                        @if($movement['type'] === 'in')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">📥 IN</span>
                                        @elseif($movement['type'] === 'out')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">📤 OUT</span>
                                        @elseif($movement['type'] === 'adjustment')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">🔧 ADJ</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ strtoupper($movement['type']) }}</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-right font-medium">
                                        {{ $this->formatStock($movement['quantity']) }} {{ $movement['unit'] }}
                                    </td>
                                    <td class="p-2 text-right text-sm text-gray-600">
                                        {{ $this->formatStock($movement['stock_before']) }}
                                    </td>
                                    <td class="p-2 text-right text-sm text-gray-600">
                                        {{ $this->formatStock($movement['stock_after']) }}
                                    </td>
                                    <td class="p-2 text-sm">{{ ucfirst($movement['reference']) }}</td>
                                    <td class="p-2 text-sm">{{ $movement['user'] }}</td>
                                    <td class="p-2 text-sm text-gray-600">{{ $movement['notes'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center p-4 text-gray-500">
                                        {{ __('report.inventory.stock_movements.no_data') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @forelse($stockMovements as $movement)
                        <div class="bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-gray-900">{{ $movement['ingredient'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $movement['date'] }}</div>
                                </div>
                                <div>
                                    @if($movement['type'] === 'in')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">IN</span>
                                    @elseif($movement['type'] === 'out')
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">OUT</span>
                                    @elseif($movement['type'] === 'adjustment')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">ADJ</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ strtoupper($movement['type']) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                 <div class="text-sm font-medium">
                                    {{ $this->formatStock($movement['quantity']) }} {{ $movement['unit'] }}
                                 </div>
                                 <div class="text-xs text-gray-500">
                                    {{ $movement['user'] }}
                                 </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 border-t pt-2">
                                <div>Before: {{ $this->formatStock($movement['stock_before']) }}</div>
                                <div class="text-right">After: {{ $this->formatStock($movement['stock_after']) }}</div>
                            </div>
                             @if($movement['notes'])
                                <div class="mt-2 text-xs text-gray-600 italic bg-gray-50 p-2 rounded">
                                    "{{ $movement['notes'] }}"
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center p-4 text-gray-500">{{ __('report.inventory.stock_movements.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>

        <div x-show="activeTab === 'purchase-orders'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4 pb-3 border-b flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">📋 {{ __('report.inventory.purchase_orders.heading') }}</h2>
                        <p class="text-sm text-gray-600 mt-1">{{ __('report.inventory.purchase_orders.description') }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="exportPurchaseOrdersExcel" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-md hover:shadow-lg transition-all duration-200 text-sm font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21,10.12H14.22L16.96,7.3C14.23,4.6 9.81,4.5 7.08,7.2C4.35,9.91 4.35,14.28 7.08,17C9.81,19.7 14.23,19.7 16.96,17C18.32,15.65 19,14.08 19,12.1H21C21,14.08 20.12,16.65 18.36,18.39C14.85,21.87 9.15,21.87 5.64,18.39C2.14,14.92 2.11,9.28 5.62,5.81C9.13,2.34 14.76,2.34 18.27,5.81L21,3V10.12M12.5,8V12.25L16,14.33L15.28,15.54L11,13V8H12.5Z"></path>
                            </svg>
                            <span>Excel</span>
                        </button>
                        <button wire:click="exportPurchaseOrdersPdf" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 shadow-md hover:shadow-lg transition-all duration-200 text-sm font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,19L12,15H9V10H15V15L13,19H10Z"></path>
                            </svg>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>
                
                {{-- Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('report.inventory.stock_movements.filters.start_date') }}</label>
                        <input type="date" wire:model="startDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('report.inventory.stock_movements.filters.end_date') }}</label>
                        <input type="date" wire:model="endDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex items-end">
                        <button wire:click="loadPurchaseOrders" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            {{ __('report.inventory.stock_movements.filters.apply') }}
                        </button>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm text-blue-600">{{ __('report.inventory.purchase_orders.stats.total_pos') }}</div>
                        <div class="text-2xl font-bold text-blue-900">{{ count($purchaseOrders) }}</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm text-green-600">{{ __('report.inventory.purchase_orders.stats.received') }}</div>
                        <div class="text-2xl font-bold text-green-900">
                            {{ collect($purchaseOrders)->where('status', 'received')->count() }}
                        </div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-sm text-yellow-600">{{ __('report.inventory.purchase_orders.stats.pending') }}</div>
                        <div class="text-2xl font-bold text-yellow-900">
                            {{ collect($purchaseOrders)->whereIn('status', ['draft', 'sent'])->count() }}
                        </div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="text-sm text-purple-600">{{ __('report.inventory.purchase_orders.stats.total_value') }}</div>
                        <div class="text-xl font-bold text-purple-900">
                            Rp {{ number_format(collect($purchaseOrders)->sum('total_amount'), 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">{{ __('report.inventory.purchase_orders.table.po_number') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.purchase_orders.table.supplier') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.purchase_orders.table.order_date') }}</th>
                                <th class="text-center p-2">{{ __('report.inventory.purchase_orders.table.status') }}</th>
                                <th class="text-center p-2">{{ __('report.inventory.purchase_orders.table.items') }}</th>
                                <th class="text-right p-2">{{ __('report.inventory.purchase_orders.table.total_amount') }}</th>
                                <th class="text-left p-2">{{ __('report.inventory.purchase_orders.table.received_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseOrders as $po)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-mono">
                                            {{ $po['po_number'] }}
                                        </span>
                                    </td>
                                    <td class="p-2 font-medium">{{ $po['supplier'] }}</td>
                                    <td class="p-2 text-sm">{{ $po['order_date'] }}</td>
                                    <td class="p-2 text-center">
                                        @if($po['status'] === 'draft')
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">📝 Draft</span>
                                        @elseif($po['status'] === 'sent')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">📨 Sent</span>
                                        @elseif($po['status'] === 'received')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">✅ Received</span>
                                        @elseif($po['status'] === 'cancelled')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">❌ Cancelled</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ ucfirst($po['status']) }}</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-center">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                            {{ $po['items_count'] }} items
                                        </span>
                                    </td>
                                    <td class="p-2 text-right font-bold">
                                        Rp {{ number_format($po['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="p-2 text-sm">{{ $po['received_date'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center p-4 text-gray-500">
                                        {{ __('report.inventory.purchase_orders.no_data') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @forelse($purchaseOrders as $po)
                        <div class="bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-gray-900">{{ $po['supplier'] }}</div>
                                    <div class="text-xs text-blue-600 font-mono">{{ $po['po_number'] }}</div>
                                </div>
                                <div>
                                    @if($po['status'] === 'draft')
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">Draft</span>
                                    @elseif($po['status'] === 'sent')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">Sent</span>
                                    @elseif($po['status'] === 'received')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">Received</span>
                                    @elseif($po['status'] === 'cancelled')
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">Cancelled</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ ucfirst($po['status']) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                                <div>
                                    <div class="text-gray-500 text-xs">Date</div>
                                    <div>{{ $po['order_date'] }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-gray-500 text-xs">Total</div>
                                    <div class="font-bold">Rp {{ number_format($po['total_amount'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-t flex justify-between text-xs text-gray-500">
                                <div>{{ $po['items_count'] }} items</div>
                                <div>{{ $po['received_date'] ?? '-' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-gray-500">{{ __('report.inventory.purchase_orders.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>

        <div x-show="activeTab === 'inventory-value'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-6 pb-3 border-b">
                    <h2 class="text-xl font-bold text-gray-900">💰 {{ __('report.inventory.inventory_value.heading') }}</h2>
                    <p class="text-sm text-gray-600 mt-1">{{ __('report.inventory.inventory_value.description') }}</p>
                </div>

                {{-- Total Value Card --}}
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-lg mb-6">
                    <div class="text-sm opacity-90">{{ __('report.inventory.inventory_value.total_value') }}</div>
                    <div class="text-4xl font-bold mt-2">
                        Rp {{ number_format($this->getTotalStockValue(), 0, ',', '.') }}
                    </div>
                </div>

                {{-- Category Breakdown --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($categoryValue as $item)
                        <div class="border rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm text-gray-600">{{ $item['category'] }}</div>
                                    <div class="text-2xl font-bold text-gray-900 mt-1">
                                        Rp {{ number_format($item['total_value'], 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    @php
                                        $percentage = $this->getTotalStockValue() > 0 
                                            ? ($item['total_value'] / $this->getTotalStockValue()) * 100 
                                            : 0;
                                    @endphp
                                    <div class="text-3xl font-bold text-blue-600">
                                        {{ number_format($percentage, 1) }}%
                                    </div>
                                    <div class="text-xs text-gray-500">{{ __('report.inventory.inventory_value.of_total') }}</div>
                                </div>
                            </div>
                            
                            {{-- Progress bar --}}
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" 
                                    style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center p-8 text-gray-500">
                            {{ __('report.inventory.inventory_value.no_data') }}
                        </div>
                    @endforelse
                </div>
            </x-filament::card>
        <div x-show="activeTab === 'variance-analysis'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-6 pb-3 border-b">
                    <h2 class="text-xl font-bold text-gray-900">📉 Variance Analysis</h2>
                    <p class="text-sm text-gray-600 mt-1">Track stock discrepancies and value loss from Stock Opnames.</p>
                </div>

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-sm text-red-600">Total Variance Loss</div>
                        <div class="text-2xl font-bold text-red-900">
                            Rp {{ number_format(collect($varianceAnalysis)->where('total_variance_value', '<', 0)->sum('total_variance_value'), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm text-green-600">Total Variance Gain</div>
                        <div class="text-2xl font-bold text-green-900">
                            Rp {{ number_format(collect($varianceAnalysis)->where('total_variance_value', '>', 0)->sum('total_variance_value'), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm text-blue-600">Net Variance</div>
                        <div class="text-2xl font-bold text-blue-900">
                            Rp {{ number_format(collect($varianceAnalysis)->sum('total_variance_value'), 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Date</th>
                                <th class="text-left p-2">Opname Number</th>
                                <th class="text-center p-2">Items Count</th>
                                <th class="text-right p-2">Variance Value</th>
                                <th class="text-center p-2">Status</th>
                                <th class="text-left p-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($varianceAnalysis as $opname)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2 text-sm">{{ $opname['date'] }}</td>
                                    <td class="p-2 font-medium">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">
                                            {{ $opname['opname_number'] }}
                                        </span>
                                    </td>
                                    <td class="p-2 text-center">{{ $opname['items_count'] }}</td>
                                    <td class="p-2 text-right font-bold {{ $opname['total_variance_value'] < 0 ? 'text-red-600' : ($opname['total_variance_value'] > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                        Rp {{ number_format($opname['total_variance_value'], 0, ',', '.') }}
                                    </td>
                                    <td class="p-2 text-center">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                                            {{ ucfirst($opname['status']) }}
                                        </span>
                                    </td>
                                    <td class="p-2 text-sm text-gray-600">{{ $opname['notes'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center p-4 text-gray-500">
                                        No Stock Opname data found for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="grid grid-cols-1 gap-4 md:hidden">
                    @forelse($varianceAnalysis as $opname)
                        <div class="bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-gray-900">{{ $opname['opname_number'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $opname['date'] }}</div>
                                </div>
                                <div>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">{{ ucfirst($opname['status']) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                 <div class="text-sm">
                                    {{ $opname['items_count'] }} Items
                                 </div>
                                 <div class="font-bold {{ $opname['total_variance_value'] < 0 ? 'text-red-600' : ($opname['total_variance_value'] > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                    Rp {{ number_format($opname['total_variance_value'], 0, ',', '.') }}
                                 </div>
                            </div>
                             @if($opname['notes'])
                                <div class="mt-2 text-xs text-gray-600 italic bg-gray-50 p-2 rounded">
                                    "{{ $opname['notes'] }}"
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center p-4 text-gray-500">No Stock Opname data found.</div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>
        </div>
        </div>
    </div>
</x-filament-panels::page>
