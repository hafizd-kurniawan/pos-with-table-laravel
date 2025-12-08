<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tenant Report - {{ $tenant->business_name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #2563EB; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; color: #1F2937; }
        
        /* Metrics Grid */
        .metrics { width: 100%; margin-bottom: 20px; }
        .metric-box { background: #f9fafb; padding: 10px; border: 1px solid #e5e7eb; border-radius: 5px; text-align: center; }
        .metric-label { font-size: 10px; color: #6b7280; text-transform: uppercase; }
        .metric-value { font-size: 18px; font-weight: bold; color: #111827; margin: 5px 0; }
        .metric-sub { font-size: 10px; }
        .text-green { color: #059669; }
        .text-red { color: #DC2626; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f3f4f6; font-weight: bold; font-size: 11px; text-transform: uppercase; color: #4b5563; }
        tr:nth-child(even) { background-color: #f9fafb; }
        
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .bg-green { background-color: #d1fae5; color: #065f46; }
        .bg-yellow { background-color: #fef3c7; color: #92400e; }
        .bg-red { background-color: #fee2e2; color: #991b1b; }
        .bg-gray { background-color: #f3f4f6; color: #1f2937; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $tenant->business_name }}</h1>
        <p>Tenant Report • {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>
        <p>Generated on {{ now()->format('d M Y H:i') }}</p>
    </div>

    <!-- Key Metrics -->
    <div class="section">
        <div class="section-title">Performance Overview</div>
        <table class="metrics">
            <tr>
                <td width="25%" style="border:none; padding: 5px;">
                    <div class="metric-box">
                        <div class="metric-label">Total Sales</div>
                        <div class="metric-value">Rp {{ number_format($dashboardData['sales_summary']['total_sales'], 0, ',', '.') }}</div>
                        <div class="metric-sub {{ $dashboardData['sales_summary']['change_percentage'] >= 0 ? 'text-green' : 'text-red' }}">
                            {{ $dashboardData['sales_summary']['change_percentage'] >= 0 ? '▲' : '▼' }} {{ abs($dashboardData['sales_summary']['change_percentage']) }}%
                        </div>
                    </div>
                </td>
                <td width="25%" style="border:none; padding: 5px;">
                    <div class="metric-box">
                        <div class="metric-label">Total Orders</div>
                        <div class="metric-value">{{ number_format($dashboardData['sales_summary']['total_orders']) }}</div>
                        <div class="metric-sub">Transactions</div>
                    </div>
                </td>
                <td width="25%" style="border:none; padding: 5px;">
                    <div class="metric-box">
                        <div class="metric-label">Avg Order Value</div>
                        <div class="metric-value">Rp {{ number_format($dashboardData['sales_summary']['avg_order'], 0, ',', '.') }}</div>
                        <div class="metric-sub">Per Transaction</div>
                    </div>
                </td>
                <td width="25%" style="border:none; padding: 5px;">
                    <div class="metric-box">
                        <div class="metric-label">Inventory Value</div>
                        <div class="metric-value">Rp {{ number_format($dashboardData['inventory_stats']['total_value'], 0, ',', '.') }}</div>
                        <div class="metric-sub">{{ $dashboardData['inventory_stats']['total_items'] }} Items</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Sales Breakdown -->
    <div class="section">
        <div class="section-title">Sales Breakdown</div>
        <table style="width: 100%">
            <tr>
                <td width="50%" valign="top" style="border:none; padding-right: 20px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px;">By Payment Method</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th style="text-align: right;">Count</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dashboardData['sales_by_payment'] as $payment)
                            <tr>
                                <td>{{ $payment['method'] }}</td>
                                <td style="text-align: right;">{{ $payment['count'] }}</td>
                                <td style="text-align: right;">Rp {{ number_format($payment['total'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td width="50%" valign="top" style="border:none;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px;">By Order Type</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th style="text-align: right;">Count</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dashboardData['sales_by_type'] as $type)
                            <tr>
                                <td>{{ $type['type'] }}</td>
                                <td style="text-align: right;">{{ $type['count'] }}</td>
                                <td style="text-align: right;">Rp {{ number_format($type['total'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Top Products -->
    <div class="section">
        <div class="section-title">Top Products</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th>Product Name</th>
                    <th style="text-align: right;">Quantity Sold</th>
                    <th style="text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dashboardData['top_products'] as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td style="text-align: right;">{{ $product['quantity'] }}</td>
                    <td style="text-align: right;">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Critical Alerts -->
    @if(!empty($dashboardData['critical_alerts']))
    <div class="section">
        <div class="section-title" style="color: #DC2626;">Critical Inventory Alerts</div>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Current Stock</th>
                    <th>Min Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dashboardData['critical_alerts'] as $alert)
                <tr>
                    <td>{{ $alert['name'] }}</td>
                    <td>{{ $alert['current_stock'] }} {{ $alert['unit'] }}</td>
                    <td>{{ $alert['min_stock'] }} {{ $alert['unit'] }}</td>
                    <td>
                        <span class="badge {{ $alert['current_stock'] <= 0 ? 'bg-red' : 'bg-yellow' }}">
                            {{ $alert['current_stock'] <= 0 ? 'Out of Stock' : 'Low Stock' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Recent Orders -->
    <div class="section">
        <div class="section-title">Recent Orders</div>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Order #</th>
                    <th>Table</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dashboardData['recent_orders'] as $order)
                <tr>
                    <td>{{ $order['time'] }}</td>
                    <td>{{ $order['order_number'] }}</td>
                    <td>{{ $order['table'] }}</td>
                    <td>Rp {{ number_format($order['grand_total'], 0, ',', '.') }}</td>
                    <td>
                        <span class="badge 
                            {{ $order['status'] === 'completed' || $order['status'] === 'paid' ? 'bg-green' : 
                               ($order['status'] === 'cooking' ? 'bg-yellow' : 
                               ($order['status'] === 'cancelled' ? 'bg-red' : 'bg-gray')) }}">
                            {{ ucfirst($order['status']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated by POS SAAS Super Admin • Page <span class="page-number"></span>
    </div>
</body>
</html>
