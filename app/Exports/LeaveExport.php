<?php

namespace App\Exports;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class LeaveExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Leave::query()->where('tenant_id', auth()->user()->tenant_id);
    }

    public function map($leave): array
    {
        return [
            $leave->user->name,
            $leave->type,
            $leave->start_date,
            $leave->end_date,
            $leave->reason,
            $leave->status,
        ];
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Type',
            'Start Date',
            'End Date',
            'Reason',
            'Status',
        ];
    }
}
