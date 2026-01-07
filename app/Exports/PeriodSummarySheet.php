<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PeriodSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $startDate;
    protected $endDate;
    
    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        $summary = $this->data['summary'];
        $comparison = $this->data['comparison'];
        
        $rows = collect();
        
        $rows->push(['LAPORAN PERIODE']);
        $rows->push(['Periode', $this->startDate . ' s/d ' . $this->endDate]);
        $rows->push(['Total Hari', $this->data['period']['days']]);
        $rows->push(['']);
        
        $rows->push(['RINGKASAN']);
        $rows->push(['Total Order', $summary['total_orders']]);
        $rows->push(['Total Items', $summary['total_items']]);
        $rows->push(['Penjualan Kotor', 'Rp ' . number_format($summary['gross_sales'], 0, ',', '.')]);
        $rows->push(['Total Diskon', 'Rp ' . number_format($summary['total_discount'], 0, ',', '.')]);
        $rows->push(['Subtotal', 'Rp ' . number_format($summary['subtotal'], 0, ',', '.')]);
        $rows->push(['Pajak', 'Rp ' . number_format($summary['total_tax'], 0, ',', '.')]);
        $rows->push(['Service', 'Rp ' . number_format($summary['total_service'], 0, ',', '.')]);
        $rows->push(['PENJUALAN BERSIH', 'Rp ' . number_format($summary['net_sales'], 0, ',', '.')]);
        $rows->push(['Rata-rata Transaksi', 'Rp ' . number_format($summary['average_transaction'], 0, ',', '.')]);
        $rows->push(['']);
        
        $rows->push(['PERBANDINGAN PERIODE SEBELUMNYA']);
        $rows->push(['Periode Sebelumnya', $comparison['previous_period']['start'] . ' s/d ' . $comparison['previous_period']['end']]);
        $rows->push(['Penjualan Sebelumnya', 'Rp ' . number_format($comparison['previous_period']['net_sales'], 0, ',', '.')]);
        $rows->push(['Penjualan Sekarang', 'Rp ' . number_format($summary['net_sales'], 0, ',', '.')]);
        $rows->push(['Pertumbuhan', $comparison['growth']['percentage'] . '%']);
        $rows->push(['Trend', strtoupper($comparison['growth']['trend'])]);
        $rows->push(['Status', strtoupper($comparison['growth']['status'])]);
        
        return $rows;
    }
    
    public function headings(): array
    {
        return [];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            5 => ['font' => ['bold' => true, 'size' => 12]],
            15 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
    
    public function title(): string
    {
        return 'Ringkasan Periode';
    }
}
