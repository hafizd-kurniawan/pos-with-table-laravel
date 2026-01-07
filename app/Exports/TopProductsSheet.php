<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TopProductsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
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
                $product['percentage'] . '%'
            ];
        });
    }
    
    public function headings(): array
    {
        return ['#', 'Nama Produk', 'Kategori', 'Qty Terjual', 'Total Penjualan', '% Kontribusi'];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }
    
    public function title(): string
    {
        return 'Produk Terlaris';
    }
}
