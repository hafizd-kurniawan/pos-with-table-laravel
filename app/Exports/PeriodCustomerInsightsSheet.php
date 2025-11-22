<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PeriodCustomerInsightsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $insights = $this->data['customer_insights'];
        $rows = collect();

        // Summary
        $rows->push(['📊 SUMMARY', '']);
        $rows->push(['Total Customers', number_format($insights['total_customers'], 0)]);
        $rows->push(['Average Spend/Customer', 'Rp ' . number_format($insights['average_spend'], 0, ',', '.')]);
        $rows->push(['', '']);

        // Growth
        $trend = $insights['growth']['trend'] === 'up' ? '↑' : '↓';
        $rows->push(['📈 GROWTH', '']);
        $rows->push(['Previous Period', $insights['growth']['previous_period_customers'] . ' customers']);
        $rows->push(['Current Period', $insights['growth']['current_period_customers'] . ' customers']);
        $rows->push(['Growth', $trend . ' ' . abs($insights['growth']['percentage']) . '%']);
        $rows->push(['', '']);

        // Top customers
        if (!empty($insights['top_customers'])) {
            $rows->push(['🏆 TOP 5 CUSTOMERS', '', '', '']);
            $rows->push(['#', 'Name', 'Orders', 'Total Spent']);

            foreach ($insights['top_customers'] as $index => $customer) {
                $loyalty = $customer['total_orders'] >= 10 ? '⭐ VIP' : ($customer['total_orders'] >= 5 ? 'Regular' : 'New');
                $rows->push([
                    $index + 1,
                    $customer['name'] . ' (' . $loyalty . ')',
                    $customer['total_orders'],
                    'Rp ' . number_format($customer['total_spent'], 0, ',', '.')
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['👥 CUSTOMER INSIGHTS'];
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
            6 => ['font' => ['bold' => true, 'size' => 12]],
            11 => ['font' => ['bold' => true, 'size' => 12]],
            12 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Customer Insights';
    }
}
