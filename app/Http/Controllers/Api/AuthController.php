<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //login
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user by email (search without tenant scope)
        $user = \App\Models\User::withoutTenantScope()->where('email', $loginData['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        // Get user's tenant
        $tenant = $user->tenant;
        
        if (!$tenant) {
            return response()->json([
                'message' => 'User has no tenant assigned. Please contact support.'
            ], 403);
        }

        // Check tenant status
        if ($tenant->status === 'suspended') {
            return response()->json([
                'message' => 'Your account is suspended. Please contact support.',
                'support_email' => 'support@possaas.com',
            ], 403);
        }

        // Check trial expiry
        if ($tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at < now()) {
            $tenant->update(['status' => 'expired']);
            
            return response()->json([
                'message' => 'Your trial period has ended. Please subscribe to continue.',
                'trial_ended_at' => $tenant->trial_ends_at->format('d M Y H:i'),
            ], 403);
        }

        // Check subscription expiry
        if ($tenant->status === 'active' && $tenant->subscription_ends_at && $tenant->subscription_ends_at < now()) {
            $tenant->update(['status' => 'expired']);
            
            return response()->json([
                'message' => 'Your subscription has expired. Please renew to continue.',
                'expired_at' => $tenant->subscription_ends_at->format('d M Y H:i'),
            ], 403);
        }

        // Check expired status
        if ($tenant->status === 'expired') {
            return response()->json([
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 403);
        }

        // Validate password
        if (!Hash::check($loginData['password'], $user->password)) {
            return response()->json(['message' => 'Invalid password'], 401);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Update is_login status
        $user->is_login = true;
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->load('tenant', 'role'),
            'tenant' => [
                'id' => $tenant->id,
                'subdomain' => $tenant->subdomain,
                'business_name' => $tenant->business_name,
                'email' => $tenant->email,
                'status' => $tenant->status,
                'status_label' => $tenant->status_label ?? ucfirst($tenant->status),
            ],
        ]);
    }

    //logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        // Update is_login status
        $user = $request->user();
        $user->is_login = false;
        $user->save();

        return response()->json(['message' => 'Logout successful']);
    }

    //update FCM token
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);
        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();
        return response()->json(['message' => 'FCM token updated successfully']);
    }
}
