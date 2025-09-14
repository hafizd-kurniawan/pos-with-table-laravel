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

        $user = \App\Models\User::where('email', $loginData['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        if (!Hash::check($loginData['password'], $user->password)) {
            return response()->json(['message' => 'Invalid password'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        // Update is_login status
        $user->is_login = true;
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
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
