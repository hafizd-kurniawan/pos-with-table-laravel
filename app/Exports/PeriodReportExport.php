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
        $sheets = [
            new PeriodSummarySheet($this->data, $this->startDate, $this->endDate),
        ];

        // Add comparison if available
        if (isset($this->data['comparison'])) {
            $sheets[] = new ComparisonSheet($this->data);
        }

        // Add payment breakdown
        if (isset($this->data['payment_breakdown'])) {
            $sheets[] = new PaymentBreakdownSheet($this->data);
        }

        // NEW: Add daily trend if available
        if (isset($this->data['daily_trend'])) {
            $sheets[] = new DailyTrendSheet($this->data);
        }

        // NEW: Add profit analysis if available
        if (isset($this->data['profit_analysis']) && !empty($this->data['profit_analysis']['products'])) {
            $sheets[] = new ProfitAnalysisSheet($this->data);
        }

        // NEW: Add customer insights if available
        if (isset($this->data['customer_insights'])) {
            $sheets[] = new PeriodCustomerInsightsSheet($this->data);
        }

        // Always add top products at the end
        if (isset($this->data['top_products']) && !empty($this->data['top_products'])) {
            $sheets[] = new TopProductsSheet($this->data['top_products']);
        }

        return $sheets;
    }
}
