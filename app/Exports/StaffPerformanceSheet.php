<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StaffPerformanceSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['staff_performance']['staff']) || empty($this->data['staff_performance']['staff'])) {
            return collect([['No staff performance data']]);
        }

        return collect($this->data['staff_performance']['staff'])->map(function($staff) {
            $medal = match($staff['rank']) {
                1 => '🏆',
                2 => '🥈',
                3 => '🥉',
                default => $staff['rank'],
            };
            
            return [
                $medal,
                $staff['user_name'],
                $staff['total_orders'],
                'Rp ' . number_format($staff['total_revenue'], 0, ',', '.'),
                'Rp ' . number_format($staff['avg_transaction'], 0, ',', '.'),
                ($staff['performance_vs_avg'] > 0 ? '+' : '') . $staff['performance_vs_avg'] . '%',
                strtoupper(str_replace('_', ' ', $staff['badge'])),
            ];
        });
    }

    public function headings(): array
    {
        return ['Rank', 'Nama Staff', 'Total Orders', 'Total Revenue', 'Avg/Transaction', 'vs Average', 'Badge'];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '16A34A']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];

        // Highlight top performer
        if (isset($this->data['staff_performance']['staff'])) {
            $rowIndex = 2;
            foreach ($this->data['staff_performance']['staff'] as $staff) {
                if ($staff['badge'] === 'top_performer') {
                    $styles[$rowIndex] = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FEF3C7']
                        ],
                        'font' => ['bold' => true],
                    ];
                } elseif ($staff['badge'] === 'above_average') {
                    $styles[$rowIndex] = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'DCFCE7']
                        ],
                    ];
                }
                
                $rowIndex++;
            }
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Staff Performance';
    }
}
