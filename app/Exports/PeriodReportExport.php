<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PeriodReportExport implements WithMultipleSheets
{
    protected $data;
    protected $startDate;
    protected $endDate;
    
    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function sheets(): array
    {
        return [
            new PeriodSummarySheet($this->data, $this->startDate, $this->endDate),
            new TopProductsSheet($this->data['top_products'] ?? []),
            new PaymentBreakdownSheet($this->data['payment_breakdown'] ?? []),
        ];
    }
}
