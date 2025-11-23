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

    {{-- COMPARISON WITH YESTERDAY --}}
    @if(isset($data['comparison']))
    <div class="section">
        <div class="section-title">📊 Perbandingan dengan Kemarin</div>
        <table style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="text-center">Hari Ini</th>
                    <th class="text-center">Kemarin</th>
                    <th class="text-center">Perubahan</th>
                    <th class="text-center">Trend</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Revenue</strong></td>
                    <td class="text-right">Rp {{ number_format($data['summary']['net_sales'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['comparison']['yesterday']['net_sales'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $data['comparison']['changes']['revenue']['percentage'] }}%</td>
                    <td class="text-center">
                        <span class="badge" style="background: {{ $data['comparison']['changes']['revenue']['trend'] === 'up' ? '#10B981' : '#EF4444' }}; color: white;">
                            {{ $data['comparison']['changes']['revenue']['trend'] === 'up' ? '↑ UP' : '↓ DOWN' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Orders</strong></td>
                    <td class="text-right">{{ $data['summary']['total_orders'] }}</td>
                    <td class="text-right">{{ $data['comparison']['yesterday']['total_orders'] }}</td>
                    <td class="text-right">{{ $data['comparison']['changes']['orders']['amount'] > 0 ? '+' : '' }}{{ $data['comparison']['changes']['orders']['amount'] }}</td>
                    <td class="text-center">
                        <span class="badge" style="background: {{ $data['comparison']['changes']['orders']['trend'] === 'up' ? '#10B981' : '#EF4444' }}; color: white;">
                            {{ $data['comparison']['changes']['orders']['trend'] === 'up' ? '↑ UP' : '↓ DOWN' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Avg/Transaction</strong></td>
                    <td class="text-right">Rp {{ number_format($data['summary']['average_transaction'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['comparison']['yesterday']['average_transaction'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $data['comparison']['changes']['average']['percentage'] }}%</td>
                    <td class="text-center">
                        <span class="badge" style="background: {{ $data['comparison']['changes']['average']['trend'] === 'up' ? '#10B981' : '#EF4444' }}; color: white;">
                            {{ $data['comparison']['changes']['average']['trend'] === 'up' ? '↑ UP' : '↓ DOWN' }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- PEAK HOURS ANALYSIS --}}
    @if(isset($data['peak_hours']) && $data['peak_hours']['busiest'])
    <div class="section">
        <div class="section-title">🕐 Analisis Jam Sibuk</div>
        <div class="breakdown-grid">
            <div class="breakdown-item">
                <div class="breakdown-label"><strong>🔥 Jam Tersibuk</strong></div>
                <div class="breakdown-value" style="color: #10B981;">{{ $data['peak_hours']['busiest']['hour'] }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Orders</div>
                <div class="breakdown-value">{{ $data['peak_hours']['busiest']['orders'] }} transaksi</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Revenue</div>
                <div class="breakdown-value">Rp {{ number_format($data['peak_hours']['busiest']['revenue'], 0, ',', '.') }}</div>
            </div>
            <div class="breakdown-item" style="margin-top: 10px;">
                <div class="breakdown-label"><strong>😴 Jam Sepi</strong></div>
                <div class="breakdown-value" style="color: #3B82F6;">{{ $data['peak_hours']['slowest']['hour'] }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Orders</div>
                <div class="breakdown-value">{{ $data['peak_hours']['slowest']['orders'] }} transaksi</div>
            </div>
        </div>
        <div style="margin-top: 15px; padding: 10px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 4px;">
            <p style="margin: 0; font-size: 11px;"><strong>💡 Rekomendasi:</strong> Tambah staff di jam {{ $data['peak_hours']['busiest']['hour'] }} untuk melayani lebih cepat!</p>
        </div>
    </div>
    @endif

    {{-- CUSTOMER INSIGHTS --}}
    @if(isset($data['customer_insights']))
    <div class="section">
        <div class="section-title">👥 Customer Insights</div>
        <div class="breakdown-grid">
            <div class="breakdown-item">
                <div class="breakdown-label">Unique Customers</div>
                <div class="breakdown-value" style="color: #8B5CF6;">{{ $data['customer_insights']['unique_customers'] }}</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Repeat Customers</div>
                <div class="breakdown-value" style="color: #10B981;">{{ $data['customer_insights']['repeat_customers'] }} ({{ $data['customer_insights']['repeat_percentage'] }}%)</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">New Customers</div>
                <div class="breakdown-value" style="color: #3B82F6;">{{ $data['customer_insights']['new_customers'] }} ({{ $data['customer_insights']['new_percentage'] }}%)</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Avg Items per Order</div>
                <div class="breakdown-value" style="color: #F59E0B;">{{ $data['customer_insights']['avg_items_per_order'] }} items</div>
            </div>
        </div>
    </div>
    @endif

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

    {{-- WEEKLY TREND --}}
    @if(isset($data['weekly_trend']) && !empty($data['weekly_trend']['days']))
    <div class="section">
        <div class="section-title" style="background: #6366F1; color: white; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px;">
            📈 TREND MINGGUAN (7 Hari Terakhir)
        </div>
        
        {{-- Summary Cards --}}
        <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
            <tr>
                <td style="width: 25%; padding: 8px; background: #EEF2FF; border: 1px solid #C7D2FE; text-align: center;">
                    <div style="font-size: 10px; color: #666;">Total (7 Hari)</div>
                    <div style="font-size: 14px; font-weight: bold; color: #6366F1;">
                        Rp {{ number_format($data['weekly_trend']['summary']['total_revenue'], 0, ',', '.') }}
                    </div>
                </td>
                <td style="width: 25%; padding: 8px; background: #F5F3FF; border: 1px solid #DDD6FE; text-align: center;">
                    <div style="font-size: 10px; color: #666;">Rata-rata/Hari</div>
                    <div style="font-size: 14px; font-weight: bold; color: #8B5CF6;">
                        Rp {{ number_format($data['weekly_trend']['summary']['average_per_day'], 0, ',', '.') }}
                    </div>
                </td>
                <td style="width: 25%; padding: 8px; background: #DCFCE7; border: 1px solid #BBF7D0; text-align: center;">
                    <div style="font-size: 10px; color: #666;">Best Day</div>
                    <div style="font-size: 14px; font-weight: bold; color: #16A34A;">
                        {{ $data['weekly_trend']['summary']['best_day']['day_short'] }} 🏆
                    </div>
                </td>
                <td style="width: 25%; padding: 8px; background: {{ $data['weekly_trend']['growth']['trend'] === 'up' ? '#DCFCE7' : '#FEE2E2' }}; border: 1px solid {{ $data['weekly_trend']['growth']['trend'] === 'up' ? '#BBF7D0' : '#FECACA' }}; text-align: center;">
                    <div style="font-size: 10px; color: #666;">Pertumbuhan</div>
                    <div style="font-size: 14px; font-weight: bold; color: {{ $data['weekly_trend']['growth']['trend'] === 'up' ? '#16A34A' : '#DC2626' }};">
                        {{ $data['weekly_trend']['growth']['trend'] === 'up' ? '↑' : '↓' }} {{ abs($data['weekly_trend']['growth']['percentage']) }}%
                    </div>
                </td>
            </tr>
        </table>
        
        {{-- Daily Breakdown --}}
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Hari</th>
                    <th style="text-align: left;">Tanggal</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['weekly_trend']['days'] as $day)
                <tr style="{{ $day['revenue'] == $data['weekly_trend']['summary']['best_day']['revenue'] ? 'background: #DCFCE7;' : '' }}">
                    <td style="font-weight: {{ $day['revenue'] == $data['weekly_trend']['summary']['best_day']['revenue'] ? 'bold' : 'normal' }};">
                        {{ $day['day_short'] }}
                        @if($day['revenue'] == $data['weekly_trend']['summary']['best_day']['revenue']) 🏆 @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                    <td class="text-center">{{ $day['orders'] }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($day['revenue'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- STOCK ALERTS --}}
    @if(isset($data['stock_alerts']) && !empty($data['stock_alerts']['alerts']))
    <div class="section" style="page-break-before: auto;">
        <div class="section-title" style="background: #DC2626; color: white; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px;">
            ⚠️ STOCK ALERTS (Peringatan Stok Rendah)
        </div>
        
        {{-- Alert Summary --}}
        <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
            <tr>
                <td style="width: 33.33%; padding: 8px; background: #FEE2E2; border: 1px solid #FECACA; text-align: center;">
                    <div style="font-size: 10px; color: #991B1B;">🔴 Critical</div>
                    <div style="font-size: 20px; font-weight: bold; color: #DC2626;">{{ $data['stock_alerts']['summary']['critical_count'] }}</div>
                    <div style="font-size: 9px; color: #DC2626;">Reorder NOW!</div>
                </td>
                <td style="width: 33.33%; padding: 8px; background: #FEF3C7; border: 1px solid #FDE68A; text-align: center;">
                    <div style="font-size: 10px; color: #92400E;">🟡 Warning</div>
                    <div style="font-size: 20px; font-weight: bold; color: #D97706;">{{ $data['stock_alerts']['summary']['warning_count'] }}</div>
                    <div style="font-size: 9px; color: #D97706;">Monitor ketat</div>
                </td>
                <td style="width: 33.33%; padding: 8px; background: #DBEAFE; border: 1px solid #BFDBFE; text-align: center;">
                    <div style="font-size: 10px; color: #1E3A8A;">🔵 Watch</div>
                    <div style="font-size: 20px; font-weight: bold; color: #2563EB;">{{ $data['stock_alerts']['summary']['watch_count'] }}</div>
                    <div style="font-size: 9px; color: #2563EB;">Siap reorder</div>
                </td>
            </tr>
        </table>
        
        {{-- Alert Details --}}
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Product</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Terjual Hari Ini</th>
                    <th class="text-center">Habis Dalam</th>
                    <th style="text-align: left;">Rekomendasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['stock_alerts']['alerts'] as $alert)
                <tr>
                    <td>
                        @if($alert['alert_level'] === 'critical') <span style="color: #DC2626;">🔴</span> @endif
                        @if($alert['alert_level'] === 'warning') <span style="color: #D97706;">🟡</span> @endif
                        @if($alert['alert_level'] === 'watch') <span style="color: #2563EB;">🔵</span> @endif
                        {{ $alert['product_name'] }}
                    </td>
                    <td class="text-center" style="font-weight: bold; color: 
                        @if($alert['alert_level'] === 'critical') #DC2626
                        @elseif($alert['alert_level'] === 'warning') #D97706
                        @else #2563EB
                        @endif;">
                        {{ $alert['current_stock'] }}
                    </td>
                    <td class="text-center">{{ $alert['sold_today'] }}</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $alert['days_until_stockout'] <= 2 ? '#DC2626' : '#666' }};">
                        {{ $alert['days_until_stockout'] }} hari
                        @if($alert['days_until_stockout'] <= 2) ⚠️ @endif
                    </td>
                    <td style="font-size: 9px;">
                        <span style="padding: 2px 6px; background: {{ $alert['recommendation'] === 'Reorder NOW!' ? '#FEE2E2' : '#FEF3C7' }}; 
                                       color: {{ $alert['recommendation'] === 'Reorder NOW!' ? '#991B1B' : '#92400E' }}; 
                                       border-radius: 3px; font-weight: bold;">
                            {{ $alert['recommendation'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- STAFF PERFORMANCE --}}
    @if(isset($data['staff_performance']) && !empty($data['staff_performance']['staff']))
    <div class="section" style="page-break-before: auto;">
        <div class="section-title" style="background: #16A34A; color: white; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px;">
            👨‍💼 STAFF PERFORMANCE (Kinerja Staff)
        </div>
        
        {{-- Performance Summary --}}
        <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
            <tr>
                <td style="width: 33.33%; padding: 8px; background: #DCFCE7; border: 1px solid #BBF7D0; text-align: center;">
                    <div style="font-size: 10px; color: #166534;">Total Staff</div>
                    <div style="font-size: 20px; font-weight: bold; color: #16A34A;">{{ $data['staff_performance']['summary']['total_staff'] }}</div>
                </td>
                <td style="width: 33.33%; padding: 8px; background: #CCFBF1; border: 1px solid #99F6E4; text-align: center;">
                    <div style="font-size: 10px; color: #134E4A;">Avg Orders/Staff</div>
                    <div style="font-size: 20px; font-weight: bold; color: #14B8A6;">{{ $data['staff_performance']['summary']['avg_orders_per_staff'] }}</div>
                </td>
                <td style="width: 33.33%; padding: 8px; background: #DBEAFE; border: 1px solid #BFDBFE; text-align: center;">
                    <div style="font-size: 10px; color: #1E3A8A;">Avg Revenue/Staff</div>
                    <div style="font-size: 14px; font-weight: bold; color: #2563EB;">
                        Rp {{ number_format($data['staff_performance']['summary']['avg_revenue_per_staff'], 0, ',', '.') }}
                    </div>
                </td>
            </tr>
        </table>
        
        {{-- Staff Leaderboard --}}
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Rank</th>
                    <th style="text-align: left;">Nama Staff</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-center">Performance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['staff_performance']['staff'] as $staff)
                <tr style="{{ $staff['badge'] === 'top_performer' ? 'background: #FEF3C7;' : '' }}">
                    <td style="font-weight: bold; font-size: 14px;">
                        @if($staff['rank'] === 1) 🏆
                        @elseif($staff['rank'] === 2) 🥈
                        @elseif($staff['rank'] === 3) 🥉
                        @else {{ $staff['rank'] }}
                        @endif
                    </td>
                    <td style="font-weight: {{ $staff['badge'] === 'top_performer' ? 'bold' : 'normal' }};">{{ $staff['user_name'] }}</td>
                    <td class="text-center">{{ $staff['total_orders'] }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($staff['total_revenue'], 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span style="padding: 2px 6px; font-size: 9px; font-weight: bold; border-radius: 3px;
                                     background: 
                                     @if($staff['badge'] === 'top_performer') #FEF3C7
                                     @elseif($staff['badge'] === 'above_average') #DCFCE7
                                     @else #F3F4F6
                                     @endif;
                                     color:
                                     @if($staff['badge'] === 'top_performer') #92400E
                                     @elseif($staff['badge'] === 'above_average') #166534
                                     @else #374151
                                     @endif;">
                            @if($staff['performance_vs_avg'] > 0) +@endif{{ $staff['performance_vs_avg'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- PROFIT ANALYSIS --}}
    @if(isset($data['profit_analysis']) && $data['profit_analysis']['summary']['gross_revenue'] > 0)
    <div class="section" style="page-break-before: auto;">
        <div class="section-title" style="background: #F59E0B; color: white; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px;">
            💰 PROFIT ANALYSIS (Analisis Keuntungan)
        </div>
        
        {{-- Summary Cards --}}
        <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
            <tr>
                <td style="width: 25%; padding: 8px; background: #DBEAFE; border: 1px solid #BFDBFE; text-align: center;">
                    <div style="font-size: 10px; color: #1E40AF;">Gross Revenue</div>
                    <div style="font-size: 14px; font-weight: bold; color: #2563EB;">
                        Rp {{ number_format($data['profit_analysis']['summary']['gross_revenue'], 0, ',', '.') }}
                    </div>
                </td>
                <td style="width: 25%; padding: 8px; background: #FEE2E2; border: 1px solid #FECACA; text-align: center;">
                    <div style="font-size: 10px; color: #991B1B;">Total COGS</div>
                    <div style="font-size: 14px; font-weight: bold; color: #DC2626;">
                        Rp {{ number_format($data['profit_analysis']['summary']['total_cogs'], 0, ',', '.') }}
                    </div>
                </td>
                <td style="width: 25%; padding: 8px; background: #DCFCE7; border: 1px solid #BBF7D0; text-align: center;">
                    <div style="font-size: 10px; color: #166534;">Net Profit</div>
                    <div style="font-size: 14px; font-weight: bold; color: #16A34A;">
                        Rp {{ number_format($data['profit_analysis']['summary']['net_profit'], 0, ',', '.') }}
                    </div>
                </td>
                <td style="width: 25%; padding: 8px; background: {{ $data['profit_analysis']['summary']['profit_margin'] >= $data['profit_analysis']['summary']['target_margin'] ? '#DCFCE7' : '#FEE2E2' }}; border: 1px solid {{ $data['profit_analysis']['summary']['profit_margin'] >= $data['profit_analysis']['summary']['target_margin'] ? '#BBF7D0' : '#FECACA' }}; text-align: center;">
                    <div style="font-size: 10px; color: #666;">Profit Margin</div>
                    <div style="font-size: 16px; font-weight: bold; color: {{ $data['profit_analysis']['summary']['profit_margin'] >= $data['profit_analysis']['summary']['target_margin'] ? '#16A34A' : '#DC2626' }};">
                        {{ $data['profit_analysis']['summary']['profit_margin'] }}%
                    </div>
                    <div style="font-size: 8px; color: #666;">Target: {{ $data['profit_analysis']['summary']['target_margin'] }}%</div>
                </td>
            </tr>
        </table>
        
        {{-- Recommendations --}}
        @if(!empty($data['profit_analysis']['recommendations']))
        <div style="margin-bottom: 15px;">
            @foreach($data['profit_analysis']['recommendations'] as $rec)
            <div style="padding: 8px; margin-bottom: 5px; background: {{ $rec['type'] === 'warning' ? '#FEF3C7' : '#DCFCE7' }}; border: 1px solid {{ $rec['type'] === 'warning' ? '#FDE68A' : '#BBF7D0' }}; border-radius: 4px;">
                <div style="font-size: 10px; font-weight: bold;">{{ $rec['icon'] }} {{ $rec['message'] }}</div>
                <div style="font-size: 9px; color: #666; margin-top: 2px;">→ {{ $rec['action'] }}</div>
            </div>
            @endforeach
        </div>
        @endif
        
        {{-- Product Profitability Table --}}
        @if(!empty($data['profit_analysis']['products']))
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">COGS</th>
                    <th class="text-right">Profit</th>
                    <th class="text-center">Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($data['profit_analysis']['products'], 0, 10) as $product)
                <tr style="{{ $product['margin'] >= 50 ? 'background: #DCFCE7;' : ($product['margin'] < 20 ? 'background: #FEE2E2;' : '') }}">
                    <td>{{ $product['product_name'] }}</td>
                    <td class="text-center">{{ $product['quantity_sold'] }}</td>
                    <td class="text-right">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</td>
                    <td class="text-right" style="color: #666;">Rp {{ number_format($product['cogs'], 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold; color: #16A34A;">Rp {{ number_format($product['profit'], 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span style="padding: 2px 6px; font-size: 9px; font-weight: bold; border-radius: 3px;
                                     background: 
                                     @if($product['margin'] >= 50) #DCFCE7
                                     @elseif($product['margin'] >= 30) #DBEAFE
                                     @elseif($product['margin'] >= 20) #FEF3C7
                                     @else #FEE2E2
                                     @endif;
                                     color:
                                     @if($product['margin'] >= 50) #166534
                                     @elseif($product['margin'] >= 30) #1E40AF
                                     @elseif($product['margin'] >= 20) #92400E
                                     @else #991B1B
                                     @endif;">
                            {{ $product['margin'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem POS</p>
        <p>© {{ now()->year }} - All Rights Reserved</p>
    </div>
</body>
</html>
