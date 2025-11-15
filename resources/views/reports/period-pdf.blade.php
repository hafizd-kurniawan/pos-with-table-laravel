<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Periode - {{ $startDate }} s/d {{ $endDate }}</title>
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
    @if(isset($data['top_products']) && count($data['top_products']) > 0)
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
                @foreach(array_slice($data['top_products'], 0, 10) as $index => $product)
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

    <div class="footer">
        <p>Generated: {{ now()->format('d/m/Y H:i:s') }} | Laporan Penjualan Periode</p>
    </div>
</body>
</html>
