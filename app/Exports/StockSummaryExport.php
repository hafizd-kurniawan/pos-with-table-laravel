<?php

namespace App\Exports;

use App\Models\Ingredient;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $tenantId;
    protected $startDate;
    protected $endDate;
    
    public function __construct($tenantId, $startDate = null, $endDate = null)
    {
        $this->tenantId = $tenantId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        return Ingredient::where('tenant_id', $this->tenantId)
            ->with(['ingredientCategory'])
            ->orderBy('name')
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'SKU',
            'Ingredient Name',
            'Category',
            'Unit',
            'Current Stock',
            'Min Stock',
            'Unit Price',
            'Stock Value',
            'Status',
        ];
    }
    
    public function map($ingredient): array
    {
        return [
            $this->cleanText($ingredient->sku),
            $this->cleanText($ingredient->name),
            $this->cleanText($ingredient->ingredientCategory?->name ?? '-'),
            $this->cleanText($ingredient->unit),
            $this->formatNumber($ingredient->current_stock),
            $this->formatNumber($ingredient->min_stock),
            'Rp ' . number_format($ingredient->cost_per_unit, 0, ',', '.'),
            'Rp ' . number_format($ingredient->stock_value, 0, ',', '.'),
            ucfirst($ingredient->stock_status),
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
    
    public function title(): string
    {
        return 'Stock Summary';
    }
    
    private function formatNumber($value)
    {
        return \App\Helpers\FormatHelper::formatStock($value);
    }
    
    private function cleanText($text)
    {
        // Remove any malformed UTF-8 characters
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // Remove invalid characters
        $text = preg_replace('/[^\x{0020}-\x{007E}\x{00A0}-\x{FFFF}]/u', '', $text);
        return $text;
    }
}
