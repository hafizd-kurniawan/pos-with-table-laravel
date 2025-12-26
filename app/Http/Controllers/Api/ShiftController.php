use Illuminate\Http\Request;
use App\Models\Shift;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::all(); // Tenant scope handled by trait
        return response()->json(['data' => $shifts]);
    }
}
