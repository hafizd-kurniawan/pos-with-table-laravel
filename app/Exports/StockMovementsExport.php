<?php

namespace App\Exports;

use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $tenantId;
    protected $startDate;
    protected $endDate;
    protected $movementType;
    
    public function __construct($tenantId, $startDate, $endDate, $movementType = null)
    {
        $this->tenantId = $tenantId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->movementType = $movementType;
    }
    
    public function collection()
    {
        $query = StockMovement::where('tenant_id', $this->tenantId)
            ->with(['ingredient', 'user'])
            ->whereBetween('moved_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);
        
        if ($this->movementType) {
            $query->where('type', $this->movementType);
        }
        
        return $query->latest('moved_at')->get();
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Ingredient',
            'Type',
            'Quantity',
            'Unit',
            'Stock Before',
            'Stock After',
            'Reference',
            'User',
            'Notes',
        ];
    }
    
    public function map($movement): array
    {
        return [
            $movement->moved_at->format('d M Y H:i'),
            $this->cleanText($movement->ingredient->name),
            strtoupper($movement->type),
            $this->formatNumber($movement->quantity),
            $this->cleanText($movement->ingredient->unit),
            $this->formatNumber($movement->stock_before),
            $this->formatNumber($movement->stock_after),
            $this->cleanText(ucfirst(str_replace('_', ' ', $movement->reference_type))),
            $this->cleanText($movement->user->name ?? '-'),
            $this->cleanText($movement->notes ?? '-'),
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
        return 'Stock Movements';
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
