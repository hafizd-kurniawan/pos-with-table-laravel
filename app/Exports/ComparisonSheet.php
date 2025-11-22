<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ComparisonSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['comparison'])) {
            return collect([['No comparison data available']]);
        }

        $comp = $this->data['comparison'];
        $summary = $this->data['summary'];

        return collect([
            [
                'Revenue',
                'Rp ' . number_format($summary['net_sales'], 0, ',', '.'),
                'Rp ' . number_format($comp['yesterday']['net_sales'], 0, ',', '.'),
                'Rp ' . number_format($comp['changes']['revenue']['amount'], 0, ',', '.'),
                $comp['changes']['revenue']['percentage'] . '%',
                strtoupper($comp['changes']['revenue']['trend']),
            ],
            [
                'Total Orders',
                $summary['total_orders'],
                $comp['yesterday']['total_orders'],
                $comp['changes']['orders']['amount'],
                $comp['changes']['orders']['percentage'] . '%',
                strtoupper($comp['changes']['orders']['trend']),
            ],
            [
                'Avg Transaction',
                'Rp ' . number_format($summary['average_transaction'], 0, ',', '.'),
                'Rp ' . number_format($comp['yesterday']['average_transaction'], 0, ',', '.'),
                '',
                $comp['changes']['average']['percentage'] . '%',
                strtoupper($comp['changes']['average']['trend']),
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Hari Ini', 'Kemarin', 'Selisih', 'Perubahan (%)', 'Trend'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '8B5CF6']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Perbandingan';
    }
}
