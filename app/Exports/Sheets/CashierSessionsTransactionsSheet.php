<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashierSessionsTransactionsSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $sessions;

    public function __construct($sessions)
    {
        $this->sessions = $sessions;
    }

    public function collection()
    {
        // Collect all transactions from all sessions
        $transactions = collect();
        foreach ($this->sessions as $session) {
            if ($session->transactions) {
                foreach ($session->transactions as $trx) {
                    // Attach session info for context
                    $trx->session_id = $session->id;
                    $trx->cashier_name = $session->user->name ?? 'Unknown';
                    $transactions->push($trx);
                }
            }
        }
        return $transactions;
    }

    public function title(): string
    {
        return 'Transactions (Pay In-Out)';
    }

    public function headings(): array
    {
        return [
            'Time',
            'Shift ID',
            'Cashier',
            'Type',
            'Amount',
            'Description',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->created_at->format('d M Y H:i'),
            $transaction->session_id,
            $transaction->cashier_name,
            strtoupper($transaction->type === 'in' ? 'PAY IN' : 'PAY OUT'),
            $transaction->amount,
            $transaction->description,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
