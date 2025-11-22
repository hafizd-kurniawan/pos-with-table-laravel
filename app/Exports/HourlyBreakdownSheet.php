<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HourlyBreakdownSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['peak_hours']['breakdown'])) {
            return collect([['No hourly data available']]);
        }

        $breakdown = collect($this->data['peak_hours']['breakdown']);
        
        // Sort by hour
        $sorted = $breakdown->sortBy('hour');

        return $sorted->map(function($hour) {
            return [
                $hour['hour'],
                $hour['orders'],
                'Rp ' . number_format($hour['revenue'], 0, ',', '.'),
                $hour['orders'] > 0 ? 'Rp ' . number_format($hour['revenue'] / $hour['orders'], 0, ',', '.') : 'Rp 0',
            ];
        });
    }

    public function headings(): array
    {
        return ['Jam', 'Jumlah Orders', 'Total Revenue', 'Avg per Order'];
    }

    public function styles(Worksheet $sheet)
    {
        // Highlight peak hours
        $peakHour = $this->data['peak_hours']['busiest']['hour'] ?? null;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Breakdown Per Jam';
    }
}
