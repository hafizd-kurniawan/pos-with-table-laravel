<?php

namespace App\Exports;

use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryValueExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $tenantId;
    
    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }
    
    public function collection()
    {
        return Ingredient::where('tenant_id', $this->tenantId)
            ->select(
                'category_id',
                DB::raw('SUM(current_stock * cost_per_unit) as total_value'),
                DB::raw('COUNT(*) as item_count')
            )
            ->with('ingredientCategory')
            ->groupBy('category_id')
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'Category',
            'Items Count',
            'Total Value',
            'Percentage',
        ];
    }
    
    public function map($item): array
    {
        $totalValue = Ingredient::where('tenant_id', $this->tenantId)
            ->sum(DB::raw('current_stock * cost_per_unit'));
        
        $percentage = $totalValue > 0 ? ($item->total_value / $totalValue) * 100 : 0;
        
        return [
            $this->cleanText($item->ingredientCategory?->name ?? 'Uncategorized'),
            $item->item_count,
            'Rp ' . number_format($item->total_value, 0, ',', '.'),
            number_format($percentage, 1) . '%',
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
        return 'Inventory Value';
    }
    
    private function cleanText($text)
    {
        if (!$text) return '';
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[^\x{0020}-\x{007E}\x{00A0}-\x{FFFF}]/u', '', $text);
        return $text;
    }
}
