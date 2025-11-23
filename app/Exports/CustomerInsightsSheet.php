<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerInsightsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if (!isset($this->data['customer_insights'])) {
            return collect([['No customer data available']]);
        }

        $insights = $this->data['customer_insights'];

        return collect([
            ['Total Orders', $insights['total_orders']],
            ['Unique Customers', $insights['unique_customers']],
            ['', ''],
            ['Repeat Customers', $insights['repeat_customers'], $insights['repeat_percentage'] . '%'],
            ['New Customers', $insights['new_customers'], $insights['new_percentage'] . '%'],
            ['', ''],
            ['Total Items Sold', $insights['total_items']],
            ['Avg Items per Order', $insights['avg_items_per_order']],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value', 'Percentage'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F59E0B']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
            'A4:C5' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5']
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Customer Insights';
    }
}
