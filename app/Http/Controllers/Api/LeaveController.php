use Illuminate\Http\Request;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $leaves = Leave::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $leaves]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sick,permit,annual_leave',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'image' => 'nullable|image|max:2048', // Optional for annual leave, required for sick usually
        ]);

        $user = Auth::user();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('leaves', 'public');
        }

        $leave = Leave::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
            'image_path' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully',
            'data' => $leave
        ], 201);
    }
}
