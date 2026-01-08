<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    /**
     * Check if member exists by phone number
     */
    public function check(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $request->phone;
        
        // Member::where will automatically apply tenant scope via BelongsToTenant
        $member = Member::where('phone', $phone)->first();

        if ($member) {
            return response()->json([
                'success' => true,
                'found' => true,
                'member' => $member
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => false,
            'message' => 'Member not found'
        ]);
    }

    /**
     * Register a new member
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        // Check availability (Tenant scope applied automatically)
        $exists = Member::where('phone', $request->phone)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Member with this phone number already exists.'
            ], 422);
        }

        $member = Member::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'total_points' => 0,
            // tenant_id is automatically set by BelongsToTenant trait
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member registered successfully',
            'member' => $member
        ], 201);
    }
}
