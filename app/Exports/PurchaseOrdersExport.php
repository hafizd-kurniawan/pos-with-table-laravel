<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $tenantId;
    protected $startDate;
    protected $endDate;
    
    public function __construct($tenantId, $startDate, $endDate)
    {
        $this->tenantId = $tenantId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        return PurchaseOrder::where('tenant_id', $this->tenantId)
            ->with(['supplier', 'items'])
            ->whereDate('order_date', '>=', $this->startDate)
            ->whereDate('order_date', '<=', $this->endDate)
            ->latest('order_date')
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'PO Number',
            'Supplier',
            'Order Date',
            'Status',
            'Items Count',
            'Total Amount',
            'Received Date',
        ];
    }
    
    public function map($po): array
    {
        return [
            $this->cleanText($po->po_number),
            $this->cleanText($po->supplier->name),
            $po->order_date->format('d M Y'),
            ucfirst($po->status),
            $po->items->count(),
            'Rp ' . number_format($po->total_amount, 0, ',', '.'),
            $po->actual_delivery_date ? $po->actual_delivery_date->format('d M Y') : '-',
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
        return 'Purchase Orders';
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
