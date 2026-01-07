<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary table {
            width: 100%;
        }
        .summary td {
            padding: 5px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data th {
            background: #673AB7;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        table.data td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table.data tr:nth-child(even) {
            background: #f9f9f9;
        }
        .status-draft { color: #9E9E9E; font-weight: bold; }
        .status-sent { color: #FF9800; font-weight: bold; }
        .status-received { color: #4CAF50; font-weight: bold; }
        .status-cancelled { color: #F44336; font-weight: bold; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $tenant }}</h1>
        <p>{{ $title }}</p>
        <p>Period: {{ $date_range }}</p>
    </div>

    <div class="summary">
        <h3>Summary</h3>
        <table>
            <tr>
                <td><strong>Total Orders:</strong> {{ count($orders) }}</td>
                <td><strong>Total Value:</strong> Rp {{ number_format(collect($orders)->sum('total_amount'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Received:</strong> {{ collect($orders)->where('status', 'received')->count() }}</td>
                <td><strong>Pending:</strong> {{ collect($orders)->whereIn('status', ['draft', 'sent'])->count() }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>PO Number</th>
                <th>Supplier</th>
                <th>Order Date</th>
                <th>Status</th>
                <th class="text-right">Items</th>
                <th class="text-right">Total Amount</th>
                <th>Received</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order['po_number'] }}</td>
                <td>{{ $order['supplier'] }}</td>
                <td>{{ $order['order_date'] }}</td>
                <td class="status-{{ $order['status'] }}">{{ ucfirst($order['status']) }}</td>
                <td class="text-right">{{ $order['items_count'] }}</td>
                <td class="text-right">Rp {{ number_format($order['total_amount'], 0, ',', '.') }}</td>
                <td>{{ $order['received_date'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report is generated automatically by {{ $tenant }} Inventory Management System</p>
        <p>Printed on {{ now()->format('d M Y H:i:s') }}</p>
    </div>
</body>
</html>
