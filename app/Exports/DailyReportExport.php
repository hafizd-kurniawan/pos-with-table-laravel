<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DailyReportExport implements WithMultipleSheets
{
    protected $data;
    protected $products;

    public function __construct($data, $products)
    {
        $this->data = $data;
        $this->products = $products;
    }

    public function sheets(): array
    {
        $sheets = [
            new SummarySheet($this->data),
        ];

        // Add comparison if available
        if (isset($this->data['comparison'])) {
            $sheets[] = new ComparisonSheet($this->data);
        }

        // Add weekly trend if available
        if (isset($this->data['weekly_trend'])) {
            $sheets[] = new WeeklyTrendSheet($this->data);
        }

        // Add hourly breakdown if available
        if (isset($this->data['peak_hours'])) {
            $sheets[] = new HourlyBreakdownSheet($this->data);
        }

        // Add customer insights if available
        if (isset($this->data['customer_insights'])) {
            $sheets[] = new CustomerInsightsSheet($this->data);
        }

        // Add stock alerts if available
        if (isset($this->data['stock_alerts']) && !empty($this->data['stock_alerts']['alerts'])) {
            $sheets[] = new StockAlertsSheet($this->data);
        }

        // Add staff performance if available
        if (isset($this->data['staff_performance']) && !empty($this->data['staff_performance']['staff'])) {
            $sheets[] = new StaffPerformanceSheet($this->data);
        }

        // Add profit analysis if available
        if (isset($this->data['profit_analysis']) && !empty($this->data['profit_analysis']['products'])) {
            $sheets[] = new ProfitAnalysisSheet($this->data);
        }

        // Always add products and payment
        $sheets[] = new ProductsSheet($this->products);
        $sheets[] = new PaymentSheet($this->data);

        return $sheets;
    }
}

// Summary Sheet
class SummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $summary = $this->data['summary'];
        
        return collect([
            ['Total Order', $summary['total_orders']],
            ['Total Items', $summary['total_items']],
            ['Total Customers', $summary['total_customers']],
            ['', ''],
            ['Penjualan Kotor', 'Rp ' . number_format($summary['gross_sales'], 0, ',', '.')],
            ['Total Diskon', 'Rp ' . number_format($summary['total_discount'], 0, ',', '.')],
            ['Subtotal', 'Rp ' . number_format($summary['subtotal'], 0, ',', '.')],
            ['Pajak (Tax)', 'Rp ' . number_format($summary['total_tax'], 0, ',', '.')],
            ['Service Charge', 'Rp ' . number_format($summary['total_service'], 0, ',', '.')],
            ['Penjualan Bersih', 'Rp ' . number_format($summary['net_sales'], 0, ',', '.')],
            ['', ''],
            ['Rata-rata Transaksi', 'Rp ' . number_format($summary['average_transaction'], 0, ',', '.')],
        ]);
    }

    public function headings(): array
    {
        return ['Keterangan', 'Nilai'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A90E2']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
            'A5:B10' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F9FF']
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }
}

// Products Sheet
class ProductsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return collect($this->products)->map(function($product, $index) {
            return [
                $index + 1,
                $product['name'],
                $product['category'],
                $product['quantity'],
                'Rp ' . number_format($product['total'], 0, ',', '.'),
                $product['percentage'] . '%',
            ];
        });
    }

    public function headings(): array
    {
        return ['#', 'Nama Produk', 'Kategori', 'Qty', 'Total Penjualan', '%'];
    }

    public function styles(Worksheet $sheet)
    {
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
        return 'Produk Terlaris';
    }
}

// Payment Sheet
class PaymentSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['payment_breakdown'])->map(function($payment) {
            $avg = $payment['count'] > 0 ? $payment['amount'] / $payment['count'] : 0;
            
            return [
                strtoupper($payment['method']),
                $payment['count'],
                'Rp ' . number_format($payment['amount'], 0, ',', '.'),
                $payment['percentage'] . '%',
                'Rp ' . number_format($avg, 0, ',', '.'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Metode Pembayaran', 'Jumlah Transaksi', 'Total Amount', 'Persentase', 'Avg/Transaction'];
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
        ];
    }

    public function title(): string
    {
        return 'Metode Pembayaran';
    }
}
