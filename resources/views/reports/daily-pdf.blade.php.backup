<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $type === 'daily' ? 'Harian' : 'Periode' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24px;
            color: #1F2937;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            color: #6B7280;
        }
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .card {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 2px solid #E5E7EB;
            background: #F9FAFB;
        }
        .card-label {
            font-size: 11px;
            color: #6B7280;
            margin-bottom: 8px;
        }
        .card-value {
            font-size: 18px;
            font-weight: bold;
            color: #1F2937;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #E5E7EB;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #F3F4F6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #D1D5DB;
            font-size: 11px;
        }
        td {
            padding: 8px 10px;
            border: 1px solid #E5E7EB;
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .breakdown-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .breakdown-item {
            display: table-row;
        }
        .breakdown-label {
            display: table-cell;
            padding: 8px;
            border-bottom: 1px solid #E5E7EB;
        }
        .breakdown-value {
            display: table-cell;
            padding: 8px;
            text-align: right;
            font-weight: bold;
            border-bottom: 1px solid #E5E7EB;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
            font-size: 10px;
            color: #9CA3AF;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #DBEAFE;
            color: #1E40AF;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <div class="header">
        <h1>LAPORAN {{ strtoupper($type === 'daily' ? 'HARIAN' : 'PERIODE') }}</h1>
        @if($type === 'daily')
            <p>Tanggal: {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</p>
        @else
            <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        @endif
        <p style="font-size: 11px; margin-top: 5px;">Dicetak: {{ now()->format('d F Y, H:i') }}</p>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="summary-cards">
        <div class="card">
            <div class="card-label">Total Order</div>
            <div class="card-value">{{ $data['summary']['total_orders'] }}</div>
        </div>
        <div class="card">
            <div class="card-label">Penjualan Kotor</div>
            <div class="card-value">Rp {{ number_format($data['summary']['gross_sales'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="card-label">Total Diskon</div>
            <div class="card-value">Rp {{ number_format($data['summary']['total_discount'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="card-label">Penjualan Bersih</div>
            <div class="card-value">Rp {{ number_format($data['summary']['net_sales'], 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- REVENUE BREAKDOWN --}}
    <div class="section">
        <div class="section-title">Rincian Pendapatan</div>
        <div class="breakdown-grid">
            <div class="breakdown-item">
                <div class="breakdown-label">Subtotal</div>
                <div class="breakdown-value">Rp {{ number_format($data['summary']['subtotal'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Pajak (Tax)</div>
                <div class="breakdown-value">Rp {{ number_format($data['summary']['total_tax'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Service Charge</div>
                <div class="breakdown-value">Rp {{ number_format($data['summary']['total_service'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Total Items</div>
                <div class="breakdown-value">{{ $data['summary']['total_items'] }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Rata-rata Transaksi</div>
                <div class="breakdown-value">Rp {{ number_format($data['summary']['average_transaction'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- PAYMENT BREAKDOWN --}}
    <div class="section">
        <div class="section-title">Metode Pembayaran</div>
        <table>
            <thead>
                <tr>
                    <th>Metode</th>
                    <th class="text-center">Jumlah Transaksi</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-center">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['payment_breakdown'] as $payment)
                <tr>
                    <td style="text-transform: uppercase;">{{ $payment['method'] }}</td>
                    <td class="text-center">{{ $payment['count'] }}</td>
                    <td class="text-right">Rp {{ number_format($payment['amount'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $payment['percentage'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- TOP PRODUCTS --}}
    @if(count($products) > 0)
    <div class="section">
        <div class="section-title">Produk Terlaris (Top 10)</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">#</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total Penjualan</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $index => $product)
                <tr>
                    <td class="text-center" style="font-weight: bold;">{{ $index + 1 }}</td>
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

    {{-- DISCOUNT & TAX SUMMARY --}}
    <div class="section">
        <div class="section-title">Ringkasan Diskon & Biaya</div>
        <div class="breakdown-grid">
            <div class="breakdown-item">
                <div class="breakdown-label">Total Diskon Diberikan</div>
                <div class="breakdown-value" style="color: #F59E0B;">Rp {{ number_format($data['summary']['total_discount'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Persentase Diskon</div>
                <div class="breakdown-value">
                    {{ $data['summary']['gross_sales'] > 0 ? number_format(($data['summary']['total_discount'] / $data['summary']['gross_sales']) * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="breakdown-item" style="margin-top: 10px;">
                <div class="breakdown-label">PPN (Tax)</div>
                <div class="breakdown-value">Rp {{ number_format($data['summary']['total_tax'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Service Charge</div>
                <div class="breakdown-value">Rp {{ number_format($data['summary']['total_service'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item" style="border-top: 2px solid #1F2937; margin-top: 5px;">
                <div class="breakdown-label" style="font-weight: bold;">Total Biaya Tambahan</div>
                <div class="breakdown-value" style="color: #8B5CF6;">Rp {{ number_format($data['summary']['total_tax'] + $data['summary']['total_service'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem POS</p>
        <p>© {{ now()->year }} - All Rights Reserved</p>
    </div>
</body>
</html>
