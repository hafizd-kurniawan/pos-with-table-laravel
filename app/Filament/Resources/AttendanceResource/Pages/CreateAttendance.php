<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $now = \Carbon\Carbon::now();
        
        // Use selected shift or find one if missing (fallback)
        $shiftId = $data['shift_id'] ?? null;
        $shift = null;

        if ($shiftId) {
            $shift = \App\Models\Shift::find($shiftId);
        } else {
            // Fallback auto-detect (should not happen if field is required)
            $shift = \App\Models\Shift::where('tenant_id', $user->tenant_id)
                ->where('start_time', '<=', $now->copy()->addHours(1)->format('H:i:s'))
                ->orderBy('start_time', 'desc')
                ->first() ?? \App\Models\Shift::where('tenant_id', $user->tenant_id)->first();
            $data['shift_id'] = $shift ? $shift->id : null;
        }

        $data['tenant_id'] = $user->tenant_id; // Ensure tenant_id is set
        $data['late_minutes'] = 0;
        $data['status'] = 'present';

        if ($shift) {
            $shiftStart = \Carbon\Carbon::parse($data['date'] . ' ' . $shift->start_time);
            $tolerance = $shift->late_tolerance_minutes;
            
            // Check if late
            $clockInTime = \Carbon\Carbon::parse($data['date'] . ' ' . $data['clock_in_time']);
            
            if ($clockInTime->gt($shiftStart->addMinutes($tolerance))) {
                $data['late_minutes'] = $shiftStart->diffInMinutes($clockInTime);
                $data['status'] = 'late';
            }
        }

        return $data;
    }
}
