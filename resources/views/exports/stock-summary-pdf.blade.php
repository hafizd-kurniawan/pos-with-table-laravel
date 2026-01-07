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
        .info {
            margin-bottom: 20px;
        }
        .info table {
            width: 100%;
        }
        .info td {
            padding: 5px;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data th {
            background: #4CAF50;
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
        .status-safe { color: #4CAF50; font-weight: bold; }
        .status-low { color: #FF9800; font-weight: bold; }
        .status-critical { color: #F44336; font-weight: bold; }
        .status-out_of_stock { color: #F44336; font-weight: bold; }
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
        <p>Generated: {{ $date }}</p>
    </div>

    <div class="summary">
        <h3>Summary</h3>
        <table>
            <tr>
                <td><strong>Total Items:</strong> {{ count($items) }}</td>
                <td><strong>Total Value:</strong> Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Ingredient</th>
                <th>Category</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Min</th>
                <th class="text-right">Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['sku'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['category'] }}</td>
                <td class="text-right">{{ $item['current_stock'] }} {{ $item['unit'] }}</td>
                <td class="text-right">{{ $item['min_stock'] }}</td>
                <td class="text-right">Rp {{ number_format($item['stock_value'], 0, ',', '.') }}</td>
                <td class="status-{{ $item['status'] }}">{{ ucfirst(str_replace('_', ' ', $item['status'])) }}</td>
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
