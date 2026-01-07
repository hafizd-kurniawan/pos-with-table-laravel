<?php

namespace App\Exports\Sheets;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashierSessionsOrdersSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $sessions;

    public function __construct($sessions)
    {
        $this->sessions = $sessions;
    }

    public function collection()
    {
        // Collect all orders from all sessions
        $allOrders = collect();
        
        foreach ($this->sessions as $session) {
            $orders = Order::with(['orderItems.product'])
                ->where('tenant_id', $session->tenant_id)
                ->where('created_at', '>=', $session->started_at)
                ->when($session->ended_at, fn($q) => $q->where('created_at', '<=', $session->ended_at))
                ->whereIn('status', ['paid', 'complete', 'completed'])
                ->get();

            foreach ($orders as $order) {
                // Attach session info for context
                $order->session_id = $session->id;
                $order->cashier_name = $session->user->name ?? 'Unknown';
                $allOrders->push($order);
            }
        }
        
        return $allOrders;
    }

    public function title(): string
    {
        return 'Orders List';
    }

    public function headings(): array
    {
        return [
            'Time',
            'Shift ID',
            'Cashier',
            'Order Code',
            'Items Summary',
            'Payment Method',
            'Total Amount',
            'Tax',
            'Service',
            'Discount',
            'Net Sales',
        ];
    }

    public function map($order): array
    {
        $itemsSummary = $order->orderItems->map(function ($item) {
            return $item->quantity . 'x ' . ($item->product->name ?? 'Unknown');
        })->join(', ');

        $netSales = $order->total_amount - $order->tax_amount - $order->service_charge_amount;

        return [
            $order->created_at->format('d M Y H:i'),
            $order->session_id,
            $order->cashier_name,
            $order->code,
            $itemsSummary,
            strtoupper($order->payment_method),
            $order->total_amount,
            $order->tax_amount,
            $order->service_charge_amount,
            $order->discount_amount,
            $netSales,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
