<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StockAlertsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['stock_alerts']['alerts']) || empty($this->data['stock_alerts']['alerts'])) {
            return collect([['No stock alerts']]);
        }

        return collect($this->data['stock_alerts']['alerts'])->map(function($alert) {
            $levelIcon = match($alert['alert_level']) {
                'critical' => '🔴',
                'warning' => '🟡',
                'watch' => '🔵',
                default => '',
            };
            
            return [
                $levelIcon . ' ' . $alert['product_name'],
                strtoupper($alert['alert_level']),
                $alert['current_stock'],
                $alert['sold_today'],
                $alert['days_until_stockout'] . ' hari',
                $alert['recommendation'],
            ];
        });
    }

    public function headings(): array
    {
        return ['Product', 'Alert Level', 'Stok Sekarang', 'Terjual Hari Ini', 'Habis Dalam', 'Rekomendasi'];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC2626']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];

        // Color code rows by alert level
        if (isset($this->data['stock_alerts']['alerts'])) {
            $rowIndex = 2;
            foreach ($this->data['stock_alerts']['alerts'] as $alert) {
                $color = match($alert['alert_level']) {
                    'critical' => 'FEE2E2',
                    'warning' => 'FEF3C7',
                    'watch' => 'DBEAFE',
                    default => 'FFFFFF',
                };
                
                $styles[$rowIndex] = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $color]
                    ],
                ];
                
                $rowIndex++;
            }
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Stock Alerts';
    }
}
