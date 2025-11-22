<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WeeklyTrendSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['weekly_trend'])) {
            return collect([['No weekly trend data available']]);
        }

        $trend = $this->data['weekly_trend'];
        
        return collect($trend['days'])->map(function($day) {
            return [
                $day['day_name'],
                $day['date'],
                $day['orders'],
                'Rp ' . number_format($day['revenue'], 0, ',', '.'),
                $day['items'],
            ];
        });
    }

    public function headings(): array
    {
        return ['Hari', 'Tanggal', 'Total Orders', 'Total Revenue', 'Total Items'];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];

        // Highlight best day if we have trend data
        if (isset($this->data['weekly_trend']['summary']['best_day'])) {
            $bestDay = $this->data['weekly_trend']['summary']['best_day']['date'];
            $rowIndex = 2; // Start from row 2 (after header)
            
            foreach ($this->data['weekly_trend']['days'] as $day) {
                if ($day['date'] === $bestDay) {
                    $styles[$rowIndex] = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'DCFCE7']
                        ],
                        'font' => ['bold' => true],
                    ];
                    break;
                }
                $rowIndex++;
            }
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Trend Mingguan';
    }
}
