<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PaymentBreakdownSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $payments;
    
    public function __construct($payments)
    {
        $this->payments = $payments;
    }
    
    public function collection()
    {
        return collect($this->payments)->map(function($payment) {
            return [
                strtoupper($payment['method']),
                $payment['count'],
                'Rp ' . number_format($payment['amount'], 0, ',', '.'),
                $payment['percentage'] . '%'
            ];
        });
    }
    
    public function headings(): array
    {
        return ['Metode Pembayaran', 'Jumlah Transaksi', 'Total Amount', '% Kontribusi'];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC000']
                ],
                'font' => ['color' => ['rgb' => '000000']]
            ],
        ];
    }
    
    public function title(): string
    {
        return 'Metode Pembayaran';
    }
}
