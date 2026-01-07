<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Complete Inventory Reports</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h1 { text-align: center; color: #1f2937; margin-bottom: 5px; }
        h2 { color: #374151; margin-top: 20px; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #3b82f6; padding-bottom: 5px; }
        h3 { color: #4b5563; margin-top: 15px; margin-bottom: 8px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .summary-box { background: #f3f4f6; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .summary-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .summary-label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background: #3b82f6; color: white; padding: 6px; text-align: left; font-size: 10px; }
        td { padding: 5px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) { background: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-safe { color: #059669; font-weight: bold; }
        .status-low { color: #d97706; font-weight: bold; }
        .status-critical { color: #dc2626; font-weight: bold; }
        .status-out { color: #991b1b; font-weight: bold; background: #fee2e2; }
        .page-break { page-break-after: always; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p><strong>{{ $tenant }}</strong></p>
        <p>Generated: {{ $date }}</p>
    </div>

    {{-- SUMMARY STATISTICS --}}
    <div class="summary-box">
        <h3>📊 Summary Statistics</h3>
        <div class="summary-row">
            <span class="summary-label">Total Ingredients:</span>
            <span>{{ $totalIngredients }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Stock Value:</span>
            <span>Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Low Stock Items:</span>
            <span style="color: #d97706; font-weight: bold;">{{ $lowStockCount }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Out of Stock:</span>
            <span style="color: #dc2626; font-weight: bold;">{{ $outOfStockCount }}</span>
        </div>
    </div>

    {{-- STOCK SUMMARY --}}
    <h2>1️⃣ Stock Summary</h2>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Ingredient</th>
                <th>Category</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Min</th>
                <th class="text-right">Value</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockSummary as $item)
                <tr>
                    <td>{{ $item['sku'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-right">{{ $item['current_stock'] }} {{ $item['unit'] }}</td>
                    <td class="text-right">{{ $item['min_stock'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['stock_value'], 0, ',', '.') }}</td>
                    <td class="text-center status-{{ $item['status'] }}">
                        @if($item['status'] === 'safe') ✅ Safe
                        @elseif($item['status'] === 'low') ⚠️ Low
                        @elseif($item['status'] === 'critical') 🔴 Critical
                        @else ❌ Out
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- LOW STOCK ALERT --}}
    <h2>2️⃣ Low Stock Alert</h2>
    <table>
        <thead>
            <tr>
                <th>Ingredient</th>
                <th class="text-right">Current</th>
                <th class="text-right">Min Required</th>
                <th class="text-right">Shortage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lowStockItems as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="text-right" style="color: #dc2626; font-weight: bold;">{{ $item['current_stock'] }} {{ $item['unit'] }}</td>
                    <td class="text-right">{{ $item['min_stock'] }} {{ $item['unit'] }}</td>
                    <td class="text-right" style="color: #dc2626; font-weight: bold;">{{ $item['shortage'] }} {{ $item['unit'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="color: #059669;">✅ All items have sufficient stock!</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- INVENTORY VALUE BY CATEGORY --}}
    <h2>3️⃣ Inventory Value by Category</h2>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Total Value</th>
                <th class="text-right">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryValue as $item)
                <tr>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['total_value'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format(($item['total_value'] / $totalValue) * 100, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- STOCK MOVEMENTS --}}
    <h2>4️⃣ Recent Stock Movements</h2>
    <p style="font-size: 10px; color: #6b7280;">Last 50 movements</p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Ingredient</th>
                <th class="text-center">Type</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Before</th>
                <th class="text-right">After</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($stockMovements, 0, 50) as $movement)
                <tr>
                    <td>{{ $movement['date'] }}</td>
                    <td>{{ $movement['ingredient'] }}</td>
                    <td class="text-center">
                        @if($movement['type'] === 'in')
                            <span style="color: #059669;">📥 IN</span>
                        @elseif($movement['type'] === 'out')
                            <span style="color: #dc2626;">📤 OUT</span>
                        @else
                            <span style="color: #d97706;">🔧 ADJ</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $movement['quantity'] }} {{ $movement['unit'] }}</td>
                    <td class="text-right">{{ $movement['stock_before'] }}</td>
                    <td class="text-right">{{ $movement['stock_after'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- PURCHASE ORDERS --}}
    <h2>5️⃣ Purchase Orders</h2>
    <table>
        <thead>
            <tr>
                <th>PO Number</th>
                <th>Supplier</th>
                <th>Date</th>
                <th class="text-center">Status</th>
                <th class="text-right">Items</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrders as $po)
                <tr>
                    <td>{{ $po['po_number'] }}</td>
                    <td>{{ $po['supplier'] }}</td>
                    <td>{{ $po['order_date'] }}</td>
                    <td class="text-center">
                        @if($po['status'] === 'draft')
                            <span style="color: #6b7280;">📝 Draft</span>
                        @elseif($po['status'] === 'sent')
                            <span style="color: #d97706;">📨 Sent</span>
                        @elseif($po['status'] === 'received')
                            <span style="color: #059669;">✅ Received</span>
                        @else
                            <span style="color: #dc2626;">❌ Cancelled</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $po['items_count'] }}</td>
                    <td class="text-right">Rp {{ number_format($po['total_amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Complete Inventory Reports - Generated {{ now()->format('d M Y H:i') }} - {{ $tenant }}</p>
    </div>
</body>
</html>
