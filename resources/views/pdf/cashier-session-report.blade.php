<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cashier Session Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .page-break {
            page-break-after: always;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 6px 8px;
            border: 1px solid #eee;
            text-align: left;
        }
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
        }
        .badge-success { background-color: #10b981; }
        .badge-warning { background-color: #f59e0b; }
        .badge-danger { background-color: #ef4444; }
        .badge-gray { background-color: #6b7280; }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .summary-col:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body>
    @foreach($sessions as $session)
        @php
            // Calculate Data (Similar to Export/Resource logic)
            $orders = \App\Models\Order::where('tenant_id', $session->tenant_id)
                ->where('created_at', '>=', $session->started_at)
                ->when($session->ended_at, fn($q) => $q->where('created_at', '<=', $session->ended_at))
                ->whereIn('status', ['paid', 'complete', 'completed'])
                ->get();

            $cashSales = $orders->where('payment_method', 'cash')->sum('total_amount');
            $grossSales = $orders->sum('total_amount');
            $tax = $orders->sum('tax_amount');
            $service = $orders->sum('service_charge_amount');
            $discount = $orders->sum('discount_amount');
            $netSales = $grossSales - $tax - $service;
            $nonCashSales = $grossSales - $cashSales;
            
            $refunds = \App\Models\Order::where('tenant_id', $session->tenant_id)
                ->where('created_at', '>=', $session->started_at)
                ->when($session->ended_at, fn($q) => $q->where('created_at', '<=', $session->ended_at))
                ->where('status', 'refunded')
                ->sum('total_amount');
            
            $expectedCash = $session->starting_cash + $cashSales + $session->total_pay_in - $session->total_pay_out;
            
            $paymentMethods = $orders->groupBy('payment_method')->map(function ($group, $method) {
                return [
                    'name' => strtoupper($method),
                    'count' => $group->count(),
                    'amount' => $group->sum('total_amount'),
                ];
            });
        @endphp

        <div class="report-container">
            <!-- Header -->
            <div class="header">
                <h1>Shift Report</h1>
                <p>Generated on {{ now()->format('d M Y H:i') }}</p>
            </div>

            <!-- Shift Info -->
            <div class="section">
                <table class="table">
                    <tr>
                        <th width="15%">Shift ID</th>
                        <td width="35%">#{{ $session->id }}</td>
                        <th width="15%">Cashier</th>
                        <td width="35%">{{ $session->user->name ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <th>Started</th>
                        <td>{{ $session->started_at->format('d M Y H:i') }}</td>
                        <th>Ended</th>
                        <td>{{ $session->ended_at ? $session->ended_at->format('d M Y H:i') : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td colspan="3">
                            <span class="badge badge-{{ $session->status === 'open' ? 'success' : ($session->status === 'closed' ? 'gray' : 'warning') }}">
                                {{ strtoupper($session->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="summary-grid">
                <!-- Cash Drawer Section -->
                <div class="summary-col">
                    <div class="section-title">Mutasi Kas (Cash Drawer)</div>
                    <table>
                        <tr>
                            <td>Modal Awal</td>
                            <td class="text-right">{{ format_rupiah($session->starting_cash) }}</td>
                        </tr>
                        <tr>
                            <td>Penjualan Tunai</td>
                            <td class="text-right text-success">+ {{ format_rupiah($cashSales) }}</td>
                        </tr>
                        <tr>
                            <td>Pay In</td>
                            <td class="text-right text-warning">+ {{ format_rupiah($session->total_pay_in) }}</td>
                        </tr>
                        <tr>
                            <td>Pay Out</td>
                            <td class="text-right text-danger">- {{ format_rupiah($session->total_pay_out) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Expected Cash</td>
                            <td class="text-right">{{ format_rupiah($expectedCash) }}</td>
                        </tr>
                        <tr>
                            <td>Actual Cash</td>
                            <td class="text-right">{{ format_rupiah($session->ending_cash) }}</td>
                        </tr>
                        <tr>
                            <td>Variance</td>
                            <td class="text-right" style="color: {{ $session->variance < 0 ? 'red' : 'green' }}">
                                {{ format_rupiah($session->variance) }}
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sales Performance Section -->
                <div class="summary-col">
                    <div class="section-title">Laporan Penjualan</div>
                    <table>
                        <tr>
                            <td>Gross Sales</td>
                            <td class="text-right">{{ format_rupiah($grossSales) }}</td>
                        </tr>
                        <tr>
                            <td>Total Refund</td>
                            <td class="text-right text-danger">{{ format_rupiah($refunds) }}</td>
                        </tr>
                        <tr>
                            <td>Tax</td>
                            <td class="text-right">- {{ format_rupiah($tax) }}</td>
                        </tr>
                        <tr>
                            <td>Service Charge</td>
                            <td class="text-right">- {{ format_rupiah($service) }}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="text-right">- {{ format_rupiah($discount) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Net Sales</td>
                            <td class="text-right">{{ format_rupiah($netSales) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border:none; height:10px;"></td>
                        </tr>
                        <tr>
                            <td>Non-Cash Sales</td>
                            <td class="text-right">{{ format_rupiah($nonCashSales) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Payment Breakdown (Detailed) -->
            <div class="section">
                <div class="section-title">Rincian Pembayaran (Detail)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th class="text-center">Trx</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Tax</th>
                            <th class="text-right">Service</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Net Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders->groupBy('payment_method') as $method => $group)
                            <tr>
                                <td>{{ strtoupper($method) }}</td>
                                <td class="text-center">{{ $group->count() }}</td>
                                <td class="text-right">{{ format_rupiah($group->sum('total_amount')) }}</td>
                                <td class="text-right">{{ format_rupiah($group->sum('tax_amount')) }}</td>
                                <td class="text-right">{{ format_rupiah($group->sum('service_charge_amount')) }}</td>
                                <td class="text-right">{{ format_rupiah($group->sum('discount_amount')) }}</td>
                                <td class="text-right">
                                    {{ format_rupiah($group->sum('total_amount') - $group->sum('tax_amount') - $group->sum('service_charge_amount')) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>TOTAL</td>
                            <td class="text-center">{{ $orders->count() }}</td>
                            <td class="text-right">{{ format_rupiah($grossSales) }}</td>
                            <td class="text-right">{{ format_rupiah($tax) }}</td>
                            <td class="text-right">{{ format_rupiah($service) }}</td>
                            <td class="text-right">{{ format_rupiah($discount) }}</td>
                            <td class="text-right">{{ format_rupiah($netSales) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Transactions List -->
            @if($session->transactions && $session->transactions->count() > 0)
            <div class="section">
                <div class="section-title">Transactions (Manual Pay In/Out)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($session->transactions as $trx)
                            <tr>
                                <td>{{ $trx->created_at->format('H:i') }}</td>
                                <td>
                                    <span class="badge badge-{{ $trx->type === 'in' ? 'success' : 'danger' }}">
                                        {{ strtoupper($trx->type === 'in' ? 'PAY IN' : 'PAY OUT') }}
                                    </span>
                                </td>
                                <td class="text-right">{{ format_rupiah($trx->amount) }}</td>
                                <td>{{ $trx->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- Orders List -->
            <div class="section">
                <div class="section-title">Orders List</div>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Order Code</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>{{ $order->created_at->format('H:i') }}</td>
                                <td>{{ $order->code }}</td>
                                <td>
                                    @foreach($order->orderItems as $item)
                                        <div>{{ $item->quantity }}x {{ $item->product->name ?? 'Unknown' }}</div>
                                    @endforeach
                                </td>
                                <td>{{ strtoupper($order->payment_method) }}</td>
                                <td class="text-right">{{ format_rupiah($order->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Notes -->
            @if($session->notes)
            <div class="section">
                <div class="section-title">Catatan</div>
                <div style="padding: 10px; background: #f9f9f9; border: 1px solid #eee;">
                    {{ $session->notes }}
                </div>
            </div>
            @endif

        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
