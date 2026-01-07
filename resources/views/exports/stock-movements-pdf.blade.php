<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data th {
            background: #2196F3;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        table.data td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        table.data tr:nth-child(even) {
            background: #f9f9f9;
        }
        .type-in { color: #4CAF50; font-weight: bold; }
        .type-out { color: #F44336; font-weight: bold; }
        .type-adjustment { color: #FF9800; font-weight: bold; }
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
        <p>Type: {{ $type }}</p>
    </div>

    <div class="info">
        <strong>Total Movements:</strong> {{ count($movements) }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ingredient</th>
                <th>Type</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Before</th>
                <th class="text-right">After</th>
                <th>Reference</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td>{{ $movement['date'] }}</td>
                <td>{{ $movement['ingredient'] }}</td>
                <td class="type-{{ $movement['type'] }}">{{ strtoupper($movement['type']) }}</td>
                <td class="text-right">{{ number_format($movement['quantity'], 0) }} {{ $movement['unit'] }}</td>
                <td class="text-right">{{ number_format($movement['stock_before'], 0) }}</td>
                <td class="text-right">{{ number_format($movement['stock_after'], 0) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $movement['reference'])) }}</td>
                <td>{{ $movement['user'] }}</td>
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
