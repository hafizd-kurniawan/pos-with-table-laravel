<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Attendance::query()->where('tenant_id', auth()->user()->tenant_id);
    }

    public function map($attendance): array
    {
        return [
            $attendance->user->name,
            $attendance->date,
            $attendance->shift->name ?? '-',
            $attendance->clock_in_time,
            $attendance->clock_out_time,
            $attendance->late_minutes,
            $attendance->status,
        ];
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Date',
            'Shift',
            'Clock In',
            'Clock Out',
            'Late Minutes',
            'Status',
        ];
    }
}
