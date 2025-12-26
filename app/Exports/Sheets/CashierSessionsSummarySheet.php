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

class CashierSessionsSummarySheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $sessions;

    public function __construct($sessions)
    {
        $this->sessions = $sessions;
    }

    public function collection()
    {
        return $this->sessions;
    }

    public function title(): string
    {
        return 'Shift Summary';
    }

    public function headings(): array
    {
        return [
            'Shift ID',
            'Cashier',
            'Started At',
            'Ended At',
            'Status',
            
            // Cash Drawer
            'Starting Cash',
            'Cash Sales (Drawer)',
            'Pay In',
            'Pay Out',
            'Expected Ending Cash',
            'Actual Ending Cash',
            'Variance',
            
            // Sales Performance
            'Gross Sales',
            'Total Refund', // Added
            'Tax',
            'Service Charge',
            'Discount',
            'Net Sales',
            'Non-Cash Sales',
            
            // Breakdown
            'Payment Breakdown',
            'Notes',
        ];
    }

    public function map($session): array
    {
        // Calculate Sales Data
        $orders = Order::where('tenant_id', $session->tenant_id)
            ->where('created_at', '>=', $session->started_at)
            ->when($session->ended_at, fn($q) => $q->where('created_at', '<=', $session->ended_at))
            ->whereIn('status', ['paid', 'complete', 'completed'])
            ->get();

        $refunds = Order::where('tenant_id', $session->tenant_id)
            ->where('created_at', '>=', $session->started_at)
            ->when($session->ended_at, fn($q) => $q->where('created_at', '<=', $session->ended_at))
            ->where('status', 'refunded')
            ->sum('total_amount');

        $cashSales = $orders->where('payment_method', 'cash')->sum('total_amount');
        
        $grossSales = $orders->sum('total_amount');
        $tax = $orders->sum('tax_amount');
        $service = $orders->sum('service_charge_amount');
        $discount = $orders->sum('discount_amount');
        $netSales = $grossSales - $tax - $service;
        
        $nonCashSales = $grossSales - $cashSales;

        // Expected Cash
        $expectedCash = $session->starting_cash + $cashSales + $session->total_pay_in - $session->total_pay_out;

        // Payment Breakdown String
        $breakdown = $orders->groupBy('payment_method')->map(function ($group, $method) {
            $amount = $group->sum('total_amount');
            return strtoupper($method) . ': ' . number_format($amount, 0, ',', '.');
        })->join(', ');

        return [
            $session->id,
            $session->user->name ?? 'Unknown',
            $session->started_at->format('d M Y H:i'),
            $session->ended_at ? $session->ended_at->format('d M Y H:i') : '-',
            ucfirst($session->status),
            
            // Cash Drawer
            $session->starting_cash,
            $cashSales,
            $session->total_pay_in,
            $session->total_pay_out,
            $expectedCash,
            $session->ending_cash,
            $session->variance,
            
            // Sales Performance
            $grossSales,
            $refunds, // Added
            $tax,
            $service,
            $discount,
            $netSales,
            $nonCashSales,
            
            // Breakdown
            $breakdown,
            $session->notes,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
