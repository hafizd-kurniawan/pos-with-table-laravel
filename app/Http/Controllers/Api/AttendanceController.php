use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // Selfie
            'location_lat' => 'required',
            'location_long' => 'required',
        ]);

        $user = Auth::user();
        $today = Carbon::today();

        // Check if already clocked in
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already clocked in today.'], 400);
        }

        // Determine Shift (Simple logic: Find shift that starts around now, or default)
        // For now, let's pick the first shift or a specific one if assigned to user
        // Ideally, User model should have 'shift_id' or we match time.
        // Let's match time: Find shift where current time is within start-end window OR close to start time
        $now = Carbon::now();
        $timeString = $now->format('H:i:s');
        
        $shift = Shift::where('tenant_id', $user->tenant_id)
            ->where('start_time', '<=', $now->copy()->addHours(1)->format('H:i:s')) // Shift starts soon or already started
            ->orderBy('start_time', 'desc')
            ->first();

        if (!$shift) {
            // Fallback: Get any shift
            $shift = Shift::where('tenant_id', $user->tenant_id)->first();
        }

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

        // Upload Image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('attendance', 'public');
        }

        $attendance = Attendance::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'shift_id' => $shift ? $shift->id : null,
            'date' => $today,
            'clock_in_time' => $now->format('H:i:s'),
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'image_path' => $imagePath,
            'location_lat' => $request->location_lat,
            'location_long' => $request->location_long,
        ]);

        return response()->json([
            'message' => 'Clock in successful',
            'data' => $attendance
        ], 201);
    }

    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'You have not clocked in today.'], 400);
        }

        if ($attendance->clock_out_time) {
            return response()->json(['message' => 'You have already clocked out today.'], 400);
        }

        $attendance->update([
            'clock_out_time' => Carbon::now()->format('H:i:s'),
        ]);

        return response()->json([
            'message' => 'Clock out successful',
            'data' => $attendance
        ]);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $history = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'data' => $history
        ]);
    }
    
    public function todayStatus()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        return response()->json([
            'data' => $attendance
        ]);
    }
}
