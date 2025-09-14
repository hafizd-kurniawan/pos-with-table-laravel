<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - Table {{ $table->name }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: white;
        }
        
        .qr-container {
            text-align: center;
            padding: 20px;
            border: 2px solid #333;
            border-radius: 10px;
            max-width: 400px;
            margin: 20px;
        }
        
        .table-info {
            margin-bottom: 20px;
        }
        
        .table-name {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .table-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .qr-code {
            margin: 20px 0;
        }
        
        .url-info {
            font-size: 12px;
            color: #888;
            word-break: break-all;
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .instructions {
            font-size: 14px;
            color: #333;
            margin-top: 15px;
            line-height: 1.4;
        }
        
        .print-buttons {
            margin: 20px 0;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-print {
            background: #4CAF50;
            color: white;
        }
        
        .btn-download {
            background: #2196F3;
            color: white;
        }
        
        .btn-back {
            background: #f44336;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <div class="table-info">
            <div class="table-name">Table {{ $table->name }}</div>
        </div>
        
        <div class="qr-code">
            {!! $qrCode !!}
        </div>
        
        <div class="instructions">
            <strong>Cara Order:</strong><br>
            1.Scan QR code dengan kamera HP<br>
            2.Pilih menu yang diinginkan<br>
            3.Lakukan pembayaran via QRIS<br>
            4.unggu pesanan diantar ke meja<br><br>
        </div>
        <div class="url-info">
            {{ $url }}
        </div>
    </div>
    
        <div class="print-buttons no-print">
            <button class="btn btn-print" onclick="window.print()">🖨️ Print QR Code</button>
            <a href="{{ route('table.download-qr', $table->id) }}" class="btn btn-download">📥 Download QR</a>
            <a href="{{ route('filament.admin.resources.tables.index') }}" class="btn btn-back">← Back to Tables</a>
        </div>    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
        
        // Close window after printing
        window.onafterprint = function() {
            // window.close();
        }
    </script>
</body>
</html>