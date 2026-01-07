<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Periode - {{ $data['period']['start'] ?? $startDate }} s/d {{ $data['period']['end'] ?? $endDate }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #4472C4;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0;
            font-size: 22px;
        }
        .header .period {
            color: #666;
            margin-top: 5px;
            font-size: 13px;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #4472C4;
            color: white;
            padding: 7px 10px;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table th {
            background: #E7E6E6;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        table td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        .summary-table td:first-child {
            font-weight: bold;
            width: 45%;
        }
        .total-row {
            background: #FFF2CC;
            font-weight: bold;
            font-size: 13px;
        }
        .comparison-row {
            background: #E7F3FF;
        }
        .growth-up {
            color: #28A745;
            font-weight: bold;
        }
        .growth-down {
            color: #DC3545;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background: #28A745;
            color: white;
        }
        .badge-danger {
            background: #DC3545;
            color: white;
        }
        .badge-warning {
            background: #FFC107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENJUALAN PERIODE</h1>
        <div class="period">
            {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
            <br>
            ({{ $data['period']['days'] }} hari - {{ ucfirst($data['period']['type']) }})
        </div>
    </div>

    {{-- SUMMARY SECTION --}}
    <div class="section">
        <div class="section-title">RINGKASAN PENJUALAN</div>
        <table class="summary-table">
            <tr>
                <td>Total Order</td>
                <td class="text-right">{{ $data['summary']['total_orders'] }} transaksi</td>
            </tr>
            <tr>
                <td>Total Items Terjual</td>
                <td class="text-right">{{ $data['summary']['total_items'] }} items</td>
            </tr>
            <tr>
                <td>Penjualan Kotor</td>
                <td class="text-right">Rp {{ number_format($data['summary']['gross_sales'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Diskon</td>
                <td class="text-right">Rp {{ number_format($data['summary']['total_discount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Subtotal</td>
                <td class="text-right">Rp {{ number_format($data['summary']['subtotal'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pajak</td>
                <td class="text-right">Rp {{ number_format($data['summary']['total_tax'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Service Charge</td>
                <td class="text-right">Rp {{ number_format($data['summary']['total_service'], 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>PENJUALAN BERSIH</td>
                <td class="text-right">Rp {{ number_format($data['summary']['net_sales'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Rata-rata Transaksi</td>
                <td class="text-right">Rp {{ number_format($data['summary']['average_transaction'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- COMPARISON SECTION --}}
    <div class="section">
        <div class="section-title">PERBANDINGAN PERIODE SEBELUMNYA</div>
        <table>
            <tr class="comparison-row">
                <td style="width: 45%;"><strong>Periode Sebelumnya</strong></td>
                <td class="text-right">
                    {{ \Carbon\Carbon::parse($data['comparison']['previous_period']['start'])->format('d M Y') }} - 
                    {{ \Carbon\Carbon::parse($data['comparison']['previous_period']['end'])->format('d M Y') }}
                </td>
            </tr>
            <tr>
                <td>Penjualan Periode Sebelumnya</td>
                <td class="text-right">Rp {{ number_format($data['comparison']['previous_period']['net_sales'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Penjualan Periode Sekarang</td>
                <td class="text-right">Rp {{ number_format($data['summary']['net_sales'], 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Selisih</td>
                <td class="text-right {{ $data['comparison']['growth']['trend'] === 'up' ? 'growth-up' : 'growth-down' }}">
                    {{ $data['comparison']['growth']['trend'] === 'up' ? '+' : '' }}
                    Rp {{ number_format($data['comparison']['growth']['amount'], 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Pertumbuhan</td>
                <td class="text-right">
                    <span class="badge badge-{{ $data['comparison']['growth']['trend'] === 'up' ? 'success' : 'danger' }}">
                        {{ $data['comparison']['growth']['trend'] === 'up' ? '↑' : '↓' }}
                        {{ abs($data['comparison']['growth']['percentage']) }}%
                    </span>
                    <span class="badge badge-{{ $data['comparison']['growth']['status'] === 'excellent' ? 'success' : ($data['comparison']['growth']['status'] === 'good' ? 'warning' : 'danger') }}">
                        {{ strtoupper($data['comparison']['growth']['status']) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- PAYMENT BREAKDOWN --}}
    <div class="section">
        <div class="section-title">METODE PEMBAYARAN</div>
        <table>
            <thead>
                <tr>
                    <th>Metode</th>
                    <th class="text-center">Transaksi</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['payment_breakdown'] as $payment)
                <tr>
                    <td><strong>{{ strtoupper($payment['method']) }}</strong></td>
                    <td class="text-center">{{ $payment['count'] }}</td>
                    <td class="text-right">Rp {{ number_format($payment['amount'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $payment['percentage'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- TOP PRODUCTS --}}
    @if(isset($products) && count($products) > 0)
    <div class="section">
        <div class="section-title">PRODUK TERLARIS</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($products, 0, 10) as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product['category'] }}</td>
                    <td class="text-center">{{ $product['quantity'] }}</td>
                    <td class="text-right">Rp {{ number_format($product['total'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $product['percentage'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- NEW: DAILY TREND ANALYSIS --}}
    @if(isset($data['daily_trend']))
    <div class="section">
        <div class="section-title">📈 TREND PENJUALAN HARIAN</div>
        <table class="summary-table">
            <tr>
                <td>Rata-rata Penjualan/Hari</td>
                <td class="text-right">Rp {{ number_format($data['daily_trend']['average'], 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>🏆 Hari Terbaik ({{ $data['daily_trend']['best_day']['date'] }})</td>
                <td class="text-right growth-up">Rp {{ number_format($data['daily_trend']['best_day']['amount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>⚠️ Hari Terburuk ({{ $data['daily_trend']['worst_day']['date'] }})</td>
                <td class="text-right growth-down">Rp {{ number_format($data['daily_trend']['worst_day']['amount'], 0, ',', '.') }}</td>
            </tr>
        </table>
        
        <div class="section-title" style="margin-top: 15px; background: #E7E6E6; color: #333;">Detail Per Hari</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Penjualan</th>
                    <th class="text-center">Performa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_trend']['labels'] as $index => $label)
                <tr>
                    <td><strong>{{ $label }}</strong></td>
                    <td class="text-center">{{ $data['daily_trend']['orders'][$index] }}</td>
                    <td class="text-right">Rp {{ number_format($data['daily_trend']['sales'][$index], 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if($data['daily_trend']['sales'][$index] >= $data['daily_trend']['average'])
                            <span class="badge badge-success">✓ Above Avg</span>
                        @else
                            <span class="badge badge-warning">Below Avg</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- NEW: PROFIT ANALYSIS --}}
    @if(isset($data['profit_analysis']))
    <div class="section" style="page-break-before: always;">
        <div class="section-title">💰 ANALISIS PROFIT & MARGIN</div>
        <table class="summary-table">
            <tr>
                <td>Total Revenue (Penjualan)</td>
                <td class="text-right">Rp {{ number_format($data['profit_analysis']['total_revenue'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total COGS (Harga Pokok)</td>
                <td class="text-right">Rp {{ number_format($data['profit_analysis']['total_cogs'], 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>NET PROFIT (Keuntungan Bersih)</td>
                <td class="text-right {{ $data['profit_analysis']['net_profit'] >= 0 ? 'growth-up' : 'growth-down' }}">
                    Rp {{ number_format($data['profit_analysis']['net_profit'], 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Margin Keuntungan</td>
                <td class="text-right">
                    <span class="badge badge-{{ $data['profit_analysis']['margin_percentage'] >= 35 ? 'success' : 'warning' }}">
                        {{ number_format($data['profit_analysis']['margin_percentage'], 1) }}%
                    </span>
                </td>
            </tr>
        </table>

        {{-- Profitable Products --}}
        @if(isset($data['profit_analysis']['products']) && count($data['profit_analysis']['products']) > 0)
        <div class="section-title" style="margin-top: 15px; background: #28A745; color: white;">Top 10 Produk Paling Menguntungkan</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th>Produk</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">COGS</th>
                    <th class="text-right">Profit</th>
                    <th class="text-center">Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($data['profit_analysis']['products'], 0, 10) as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product['product_name'] }}</td>
                    <td class="text-center">{{ $product['quantity_sold'] }}</td>
                    <td class="text-right">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($product['cogs'], 0, ',', '.') }}</td>
                    <td class="text-right {{ $product['profit'] >= 0 ? 'growth-up' : 'growth-down' }}">
                        Rp {{ number_format($product['profit'], 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $product['margin'] >= 50 ? 'success' : ($product['margin'] >= 30 ? 'warning' : 'danger') }}">
                            {{ $product['margin'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Recommendations --}}
        @if(isset($data['profit_analysis']['recommendations']) && count($data['profit_analysis']['recommendations']) > 0)
        <div class="section-title" style="margin-top: 15px; background: #FFC107; color: #000;">💡 Rekomendasi</div>
        <table>
            @foreach($data['profit_analysis']['recommendations'] as $rec)
            <tr>
                <td style="padding: 8px;">
                    <strong>{{ $rec['icon'] }} {{ $rec['message'] }}</strong><br>
                    <small style="color: #666;">→ {{ $rec['action'] }}</small>
                </td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif

    {{-- NEW: CUSTOMER INSIGHTS --}}
    @if(isset($data['customer_insights']))
    <div class="section">
        <div class="section-title">👥 CUSTOMER INSIGHTS</div>
        <table class="summary-table">
            <tr>
                <td>Total Customers (Unique)</td>
                <td class="text-right">{{ number_format($data['customer_insights']['total_customers'], 0) }} customers</td>
            </tr>
            <tr>
                <td>Rata-rata Spending/Customer</td>
                <td class="text-right">Rp {{ number_format($data['customer_insights']['average_spend'], 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Customer Growth vs Periode Sebelumnya</td>
                <td class="text-right {{ $data['customer_insights']['growth']['trend'] === 'up' ? 'growth-up' : 'growth-down' }}">
                    {{ $data['customer_insights']['growth']['trend'] === 'up' ? '↑' : '↓' }}
                    {{ abs($data['customer_insights']['growth']['percentage']) }}%
                    ({{ $data['customer_insights']['growth']['previous_period_customers'] }} → {{ $data['customer_insights']['growth']['current_period_customers'] }})
                </td>
            </tr>
        </table>

        {{-- Top Customers --}}
        @if(isset($data['customer_insights']['top_customers']) && count($data['customer_insights']['top_customers']) > 0)
        <div class="section-title" style="margin-top: 15px; background: #4472C4;">Top 5 Customers (By Spending)</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th>Customer Name</th>
                    <th class="text-center">Total Orders</th>
                    <th class="text-right">Total Spent</th>
                    <th class="text-center">Loyalty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['customer_insights']['top_customers'] as $index => $customer)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $customer['name'] }}</strong></td>
                    <td class="text-center">{{ $customer['total_orders'] }}</td>
                    <td class="text-right">Rp {{ number_format($customer['total_spent'], 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if($customer['total_orders'] >= 10)
                            <span class="badge badge-success">⭐ VIP</span>
                        @elseif($customer['total_orders'] >= 5)
                            <span class="badge badge-warning">Regular</span>
                        @else
                            <span class="badge" style="background: #E7E6E6; color: #333;">New</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Generated: {{ now()->format('d/m/Y H:i:s') }} | Laporan Penjualan Periode (Comprehensive Report)</p>
    </div>
</body>
</html>
