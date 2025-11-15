<?php

namespace App\Exports;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllInventoryExport implements WithMultipleSheets
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
    
    public function sheets(): array
    {
        return [
            'Stock Summary' => new StockSummaryExport($this->tenantId, $this->startDate, $this->endDate),
            'Stock Movements' => new StockMovementsExport($this->tenantId, $this->startDate, $this->endDate),
            'Purchase Orders' => new PurchaseOrdersExport($this->tenantId, $this->startDate, $this->endDate),
            'Low Stock Alert' => new LowStockExport($this->tenantId),
            'Inventory Value' => new InventoryValueExport($this->tenantId),
        ];
    }
}
