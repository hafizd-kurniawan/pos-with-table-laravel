<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Attendance;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AttendanceWidget extends BaseWidget
{
    protected static ?int $sort = -2; // Show at top

    protected function getStats(): array
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $actions = [];
        $description = 'You have not clocked in today';
        $color = 'danger';
        $icon = 'heroicon-o-clock';
        $value = 'Not Present';
        $extraAttributes = [];

        if (!$attendance) {
            // Not Clocked In
            $value = 'Not Present';
            $description = 'Click to start your shift';
            $color = 'danger';
            $icon = 'heroicon-o-x-circle';
            $extraAttributes = [
                'class' => 'cursor-pointer',
                'wire:click' => 'clockIn',
            ];
        } elseif ($attendance->clock_out_time) {
            // Already Clocked Out
            $value = 'Shift Completed';
            $description = 'In: ' . Carbon::parse($attendance->clock_in_time)->format('H:i') . 
                           ' | Out: ' . Carbon::parse($attendance->clock_out_time)->format('H:i');
            $color = 'success';
            $icon = 'heroicon-o-check-circle';
        } else {
            // Clocked In, waiting to Clock Out
            $value = 'Working';
            $description = 'Clock In at ' . Carbon::parse($attendance->clock_in_time)->format('H:i') . '. Click to Clock Out.';
            $color = 'warning';
            $icon = 'heroicon-o-briefcase';
            $extraAttributes = [
                'class' => 'cursor-pointer',
                'wire:click' => 'clockOut',
            ];
        }

        return [
            Stat::make('Attendance Status', $value)
                ->description($description)
                ->descriptionIcon($icon)
                ->color($color)
                ->extraAttributes($extraAttributes),
        ];
    }
    
    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();
        
        // Logic similar to Controller
        $shift = Shift::where('tenant_id', $user->tenant_id)
            ->where('start_time', '<=', $now->copy()->addHours(1)->format('H:i:s'))
            ->orderBy('start_time', 'desc')
            ->first() ?? Shift::where('tenant_id', $user->tenant_id)->first();

        $lateMinutes = 0;
        $status = 'present';

        if ($shift) {
            $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->start_time);
            $tolerance = $shift->late_tolerance_minutes;
            
            if ($now->gt($shiftStart->addMinutes($tolerance))) {
                $lateMinutes = $shiftStart->diffInMinutes($now);
                $status = 'late';
            }
        }

        Attendance::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'shift_id' => $shift ? $shift->id : null,
            'date' => $today,
            'clock_in_time' => $now->format('H:i:s'),
            'late_minutes' => $lateMinutes,
            'status' => $status,
        ]);

        Notification::make()
            ->title('Clock In Successful')
            ->success()
            ->send();
            
        $this->dispatch('refresh'); // Refresh widget
    }

    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && !$attendance->clock_out_time) {
            $attendance->update([
                'clock_out_time' => Carbon::now()->format('H:i:s'),
            ]);
            
            Notification::make()
                ->title('Clock Out Successful')
                ->success()
                ->send();
                
            $this->dispatch('refresh');
        }
    }
}
