<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfitAnalysisSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['profit_analysis']['products']) || empty($this->data['profit_analysis']['products'])) {
            return collect([['No profit data available']]);
        }

        return collect($this->data['profit_analysis']['products'])->map(function($product) {
            return [
                $product['product_name'],
                $product['quantity_sold'],
                'Rp ' . number_format($product['revenue'], 0, ',', '.'),
                'Rp ' . number_format($product['cogs'], 0, ',', '.'),
                'Rp ' . number_format($product['profit'], 0, ',', '.'),
                $product['margin'] . '%',
                $product['margin'] >= 50 ? 'Excellent' : ($product['margin'] >= 30 ? 'Good' : ($product['margin'] >= 20 ? 'Fair' : 'Low')),
            ];
        });
    }

    public function headings(): array
    {
        return ['Product', 'Qty Sold', 'Revenue', 'COGS', 'Profit', 'Margin %', 'Status'];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F59E0B']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];

        // Color code rows by margin
        if (isset($this->data['profit_analysis']['products'])) {
            $rowIndex = 2;
            foreach ($this->data['profit_analysis']['products'] as $product) {
                $color = 'FFFFFF';
                if ($product['margin'] >= 50) {
                    $color = 'DCFCE7'; // Green
                } elseif ($product['margin'] < 20) {
                    $color = 'FEE2E2'; // Red
                }
                
                if ($color !== 'FFFFFF') {
                    $styles[$rowIndex] = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
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
        return 'Profit Analysis';
    }
}
