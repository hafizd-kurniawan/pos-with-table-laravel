<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DailyTrendSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $trend = $this->data['daily_trend'];
        $rows = collect();

        // Summary rows
        $rows->push(['📊 SUMMARY', '']);
        $rows->push(['Rata-rata/Hari', 'Rp ' . number_format($trend['average'], 0, ',', '.')]);
        $rows->push(['Hari Terbaik', $trend['best_day']['date'] . ' - Rp ' . number_format($trend['best_day']['amount'], 0, ',', '.')]);
        $rows->push(['Hari Terburuk', $trend['worst_day']['date'] . ' - Rp ' . number_format($trend['worst_day']['amount'], 0, ',', '.')]);
        $rows->push(['', '']); // Empty row

        // Detail header
        $rows->push(['📅 DETAIL HARIAN', '', '', '']);
        $rows->push(['Tanggal', 'Orders', 'Penjualan', 'Status']);

        // Daily details
        foreach ($trend['labels'] as $index => $label) {
            $sales = $trend['sales'][$index];
            $orders = $trend['orders'][$index];
            $status = $sales >= $trend['average'] ? '✓ Above Average' : 'Below Average';

            $rows->push([
                $label,
                $orders,
                'Rp ' . number_format($sales, 0, ',', '.'),
                $status
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['📈 TREND PENJUALAN HARIAN'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            7 => ['font' => ['bold' => true, 'size' => 12]],
            8 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Daily Trend';
    }
}
