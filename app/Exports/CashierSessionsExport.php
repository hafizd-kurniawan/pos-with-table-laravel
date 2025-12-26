<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\CashierSessionsSummarySheet;
use App\Exports\Sheets\CashierSessionsTransactionsSheet;
use App\Exports\Sheets\CashierSessionsOrdersSheet;

class CashierSessionsExport implements WithMultipleSheets
{
    protected $sessions;

    public function __construct($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Summary (Session Level)
        $sheets[] = new CashierSessionsSummarySheet($this->sessions);

        // Sheet 2: Transactions (Pay In/Out)
        $sheets[] = new CashierSessionsTransactionsSheet($this->sessions);

        // Sheet 3: Orders (Detailed Orders List)
        $sheets[] = new CashierSessionsOrdersSheet($this->sessions);

        return $sheets;
    }
}
