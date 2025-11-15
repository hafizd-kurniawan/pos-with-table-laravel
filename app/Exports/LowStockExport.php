<?php

namespace App\Exports;

use App\Models\Ingredient;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LowStockExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $tenantId;
    
    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }
    
    public function collection()
    {
        return Ingredient::where('tenant_id', $this->tenantId)
            ->with(['ingredientCategory'])
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock')
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'SKU',
            'Ingredient',
            'Category',
            'Current Stock',
            'Min Stock',
            'Shortage',
            'Unit',
            'Status',
        ];
    }
    
    public function map($ingredient): array
    {
        $shortage = max(0, $ingredient->min_stock - $ingredient->current_stock);
        
        return [
            $this->cleanText($ingredient->sku),
            $this->cleanText($ingredient->name),
            $this->cleanText($ingredient->ingredientCategory?->name ?? '-'),
            $this->formatNumber($ingredient->current_stock),
            $this->formatNumber($ingredient->min_stock),
            $this->formatNumber($shortage),
            $this->cleanText($ingredient->unit),
            $ingredient->current_stock == 0 ? 'Out of Stock' : 'Low Stock',
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
        return 'Low Stock Alert';
    }
    
    private function formatNumber($value)
    {
        return \App\Helpers\FormatHelper::formatStock($value);
    }
    
    private function cleanText($text)
    {
        if (!$text) return '';
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[^\x{0020}-\x{007E}\x{00A0}-\x{FFFF}]/u', '', $text);
        return $text;
    }
}
