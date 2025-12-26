<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\CashierSessionTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierSessionController extends Controller
{
    /**
     * Get current active session for the user
     */
    public function current(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $session = CashierSession::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active shift found'
            ]);
        }

        // Calculate current stats on the fly
        $stats = $this->calculateSessionStats($session);

        // Fetch ACTIVE orders (paid) for manual "Pay In"
        // Same logic as show()
        $activeOrders = Order::where('tenant_id', $session->tenant_id)
            ->where('created_at', '>=', $session->started_at)
            // No ended_at check for current session as it is open
            ->where('status', 'paid') 
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter out orders that have already been added as transactions
        $existingDescriptions = $session->transactions->pluck('description')->map(function ($desc) {
            return strtolower($desc);
        })->toArray();

        $activeOrders = $activeOrders->filter(function ($order) use ($existingDescriptions) {
            $expectedDescription = strtolower("Pay In from " . $order->code);
            return !in_array($expectedDescription, $existingDescriptions);
        })->values();

        $sessionData = $session->toArray();
        $sessionData['active_orders'] = $activeOrders;

        return response()->json([
            'success' => true,
            'data' => array_merge($sessionData, $stats)
        ]);
    }

    /**
     * Open a new shift
     */
    public function open(Request $request)
    {
        $request->validate([
            'starting_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Check if already open
        $existing = CashierSession::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an open shift.'
            ], 400);
        }

        $session = CashierSession::create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'starting_cash' => $request->starting_cash,
            'status' => 'open',
            'started_at' => now(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift opened successfully',
            'data' => $session
        ], 201);
    }

    /**
     * Add Pay In / Pay Out transaction
     */
    public function transaction(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        $session = CashierSession::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift found.'
            ], 404);
        }

        $transaction = $session->transactions()->create([
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        // Update session totals
        if ($request->type === 'in') {
            $session->increment('total_pay_in', $request->amount);
        } else {
            $session->increment('total_pay_out', $request->amount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded',
            'data' => $transaction
        ]);
    }

    /**
     * Close the shift
     */
    public function close(Request $request)
    {
        $request->validate([
            'ending_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        $session = CashierSession::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift found.'
            ], 404);
        }

        // Calculate final stats
        $stats = $this->calculateSessionStats($session);
        
        $session->update([
            'status' => 'closed',
            'ended_at' => now(),
            'ending_cash' => $request->ending_cash,
            'cash_sales' => $stats['cash_sales'],
            'cash_refunds' => $stats['cash_refunds'],
            'expected_ending_cash' => $stats['expected_ending_cash'],
            'variance' => $request->ending_cash - $stats['expected_ending_cash'],
            'notes' => $request->notes ? ($session->notes . "\n" . $request->notes) : $session->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift closed successfully',
            'data' => $session
        ]);
    }

    /**
     * Get shift history (paginated)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;
        
        $limit = $request->input('limit', 10);

        $history = CashierSession::where('tenant_id', $tenantId)
            // Optional: Filter by user if not admin? For now show all for tenant or just own?
            // Usually managers want to see all. Let's show all for now.
            ->where('status', 'closed')
            ->with('user:id,name,email')
            ->latest('ended_at')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Get shift details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $session = CashierSession::where('tenant_id', $tenantId)
            ->with(['user:id,name,email', 'transactions'])
            ->find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Shift session not found'
            ], 404);
        }

        // Calculate stats (or use stored ones if reliable)
        // For closed sessions, stored stats in DB should be final. 
        // But let's recalculate to be sure or just return stored.
        // The 'calculateSessionStats' method is useful for verifying.
        // Let's return the stored data + transactions + ORDERS.
        
        $orders = Order::where('tenant_id', $session->tenant_id)
            // If we had user_id on orders, we would filter by it. 
            // Assuming current user is the one who made the orders? 
            // Or just filter by time range of the session.
            // Ideally Order should have user_id (cashier).
            // Let's check if Order has user_id. Yes, belongsTo User.
            // But wait, Order model doesn't have user_id in fillable?
            // Let's check migration or assume it's there.
            // The Order model has `public function user()`.
            // So we filter by user_id if possible.
            // Actually, let's just use the time range logic from calculateSessionStats.
            ->where('created_at', '>=', $session->started_at)
            ->when($session->ended_at, function($q) use ($session) {
                $q->where('created_at', '<=', $session->ended_at);
            })
            ->where('status', 'paid') // Only paid orders
            ->with(['orderItems.product']) // Include items
            ->orderBy('created_at', 'desc')
            ->get();

        $session->orders = $orders;

        // Fetch ACTIVE orders (pending, cooking, served) for manual "Pay In"
        // This allows cashiers to manually add money from ongoing orders if needed
        $activeOrders = Order::where('tenant_id', $session->tenant_id)
            ->where('created_at', '>=', $session->started_at)
            ->when($session->ended_at, function($q) use ($session) {
                $q->where('created_at', '<=', $session->ended_at);
            })
            ->where('status', 'paid') // Only PAID orders
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter out orders that have already been added as transactions
        // We assume the description format is "Pay In from {OrderCode}"
        $existingDescriptions = $session->transactions->pluck('description')->map(function ($desc) {
            return strtolower($desc);
        })->toArray();

        $activeOrders = $activeOrders->filter(function ($order) use ($existingDescriptions) {
            $expectedDescription = strtolower("Pay In from " . $order->code);
            return !in_array($expectedDescription, $existingDescriptions);
        })->values(); // Reset keys

        $session->active_orders = $activeOrders;

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Calculate stats for a session
     */
    private function calculateSessionStats(CashierSession $session)
    {
        // Get orders completed within this session by this user
        // Note: We use created_at for simplicity, but ideally should link order to session_id
        $orders = Order::where('tenant_id', $session->tenant_id)
            ->where('created_at', '>=', $session->started_at)
            ->when($session->ended_at, function($q) use ($session) {
                $q->where('created_at', '<=', $session->ended_at);
            })
            ->where('status', 'paid') // Only paid orders
            ->get();

        $cashSales = $orders->where('payment_method', 'cash')->sum('total_amount');
        // Assuming no refunds implemented yet, but placeholder:
        $cashRefunds = 0; 

        $expectedEndingCash = $session->starting_cash + $cashSales + $session->total_pay_in - $session->total_pay_out - $cashRefunds;

        return [
            'cash_sales' => $cashSales,
            'cash_refunds' => $cashRefunds,
            'expected_ending_cash' => $expectedEndingCash,
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_amount'), // All methods
        ];
    }
}
