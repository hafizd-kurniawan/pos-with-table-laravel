<x-filament-panels::page>
    {{-- EXPORT BUTTONS - EXCEL ONLY (WORKING) --}}
    <div style="margin-bottom: 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="font-size: 20px; font-weight: bold; margin-bottom: 8px; color: white;">📊 Export Complete Inventory Reports</h3>
        <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin-bottom: 16px;">
            ✅ Export all data to Excel: Stock Summary, Movements, Purchase Orders, Low Stock Alert & Inventory Value<br>
            📑 All reports in one file with 5 organized sheets - Perfect for analysis!
        </p>
        
        <button wire:click="exportAllExcel" 
            style="display: inline-block; padding: 16px 32px; background-color: white; color: #059669; border: none; border-radius: 10px; font-weight: bold; font-size: 18px; cursor: pointer; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); transition: all 0.3s;">
            📊 EXPORT ALL TO EXCEL (5 SHEETS)
        </button>
        
        <div style="margin-top: 12px; padding: 8px 12px; background: rgba(255,255,255,0.2); border-radius: 6px; font-size: 12px; color: white;">
            💡 Tip: Excel export works perfectly with Indonesian text and all special characters. Open in Excel, Google Sheets, or LibreOffice!
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Total Ingredients</div>
            <div class="text-2xl font-bold text-gray-900">{{ $this->getTotalIngredients() }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Total Stock Value</div>
            <div class="text-2xl font-bold text-green-600">Rp {{ number_format($this->getTotalStockValue(), 0, ',', '.') }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Low Stock Items</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $this->getLowStockCount() }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Out of Stock</div>
            <div class="text-2xl font-bold text-red-600">{{ $this->getOutOfStockCount() }}</div>
        </x-filament::card>
    </div>

    {{-- Tabs and Content --}}
    <div x-data="{ activeTab: @entangle('activeTab').defer }" x-init="if(!activeTab) activeTab = 'stock-summary'">
        {{-- Tabs Navigation --}}
        <div class="mb-6">
            <div class="border-b border-gray-200 bg-white rounded-t-lg">
                <nav class="-mb-px flex space-x-8 px-4">
                    <button @click="activeTab = 'stock-summary'" 
                        :class="activeTab === 'stock-summary' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📊 Stock Summary
                    </button>
                    
                    <button @click="activeTab = 'stock-movements'" 
                        :class="activeTab === 'stock-movements' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📦 Stock Movements
                    </button>
                    
                    <button @click="activeTab = 'purchase-orders'" 
                        :class="activeTab === 'purchase-orders' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        📋 Purchase Orders
                    </button>
                    
                    <button @click="activeTab = 'inventory-value'" 
                        :class="activeTab === 'inventory-value' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        💰 Inventory Value
                    </button>
                    
                    <button @click="activeTab = 'low-stock'" 
                        :class="activeTab === 'low-stock' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-sm transition-all">
                        ⚠️ Low Stock Alert
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
                        <h2 class="text-xl font-bold text-gray-900">📊 Stock Summary</h2>
                        <p class="text-sm text-gray-600 mt-1">Overview of all ingredients with current stock levels and values</p>
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
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">SKU</th>
                                <th class="text-left p-2">Ingredient</th>
                                <th class="text-left p-2">Category</th>
                                <th class="text-right p-2">Stock</th>
                                <th class="text-right p-2">Min Stock</th>
                                <th class="text-right p-2">Value</th>
                                <th class="text-center p-2">Status</th>
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
            </x-filament::card>
        </div>

        <div x-show="activeTab === 'low-stock'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-red-600">⚠️ Low Stock Alert</h3>
                    <p class="text-sm text-gray-600">Items that need restocking</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Ingredient</th>
                                <th class="text-right p-2">Current Stock</th>
                                <th class="text-right p-2">Min Stock</th>
                                <th class="text-right p-2">Shortage</th>
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
                                        ✅ All ingredients have sufficient stock!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        </div>
        
        <div x-show="activeTab === 'stock-movements'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4 pb-3 border-b flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">📦 Stock Movement History</h2>
                        <p class="text-sm text-gray-600 mt-1">Track all inventory transactions - incoming, outgoing, and adjustments</p>
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
                        <label class="text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" wire:model="startDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" wire:model="endDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Movement Type</label>
                        <select wire:model="movementType" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            <option value="in">In (Masuk)</option>
                            <option value="out">Out (Keluar)</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="loadStockMovements" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Apply Filters
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Date</th>
                                <th class="text-left p-2">Ingredient</th>
                                <th class="text-center p-2">Type</th>
                                <th class="text-right p-2">Quantity</th>
                                <th class="text-right p-2">Before</th>
                                <th class="text-right p-2">After</th>
                                <th class="text-left p-2">Reference</th>
                                <th class="text-left p-2">User</th>
                                <th class="text-left p-2">Notes</th>
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
                                        No stock movements found for selected period
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        </div>

        <div x-show="activeTab === 'purchase-orders'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-4 pb-3 border-b flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">📋 Purchase Orders Report</h2>
                        <p class="text-sm text-gray-600 mt-1">Monitor all purchase orders with summary analytics and detailed breakdown</p>
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
                        <label class="text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" wire:model="startDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" wire:model="endDate" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex items-end">
                        <button wire:click="loadPurchaseOrders" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Apply Filters
                        </button>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm text-blue-600">Total POs</div>
                        <div class="text-2xl font-bold text-blue-900">{{ count($purchaseOrders) }}</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm text-green-600">Received</div>
                        <div class="text-2xl font-bold text-green-900">
                            {{ collect($purchaseOrders)->where('status', 'received')->count() }}
                        </div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-sm text-yellow-600">Pending</div>
                        <div class="text-2xl font-bold text-yellow-900">
                            {{ collect($purchaseOrders)->whereIn('status', ['draft', 'sent'])->count() }}
                        </div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="text-sm text-purple-600">Total Value</div>
                        <div class="text-xl font-bold text-purple-900">
                            Rp {{ number_format(collect($purchaseOrders)->sum('total_amount'), 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">PO Number</th>
                                <th class="text-left p-2">Supplier</th>
                                <th class="text-left p-2">Order Date</th>
                                <th class="text-center p-2">Status</th>
                                <th class="text-center p-2">Items</th>
                                <th class="text-right p-2">Total Amount</th>
                                <th class="text-left p-2">Received Date</th>
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
                                        No purchase orders found for selected period
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        </div>

        <div x-show="activeTab === 'inventory-value'" x-cloak style="display: none;">
            <x-filament::card>
                <div class="mb-6 pb-3 border-b">
                    <h2 class="text-xl font-bold text-gray-900">💰 Inventory Value Analysis</h2>
                    <p class="text-sm text-gray-600 mt-1">Total stock value grouped by ingredient category with percentage breakdown</p>
                </div>

                {{-- Total Value Card --}}
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-lg mb-6">
                    <div class="text-sm opacity-90">Total Inventory Value</div>
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
                                    <div class="text-xs text-gray-500">of total</div>
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
                            No inventory data available
                        </div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>
        </div>
    </div>
</x-filament-panels::page>
