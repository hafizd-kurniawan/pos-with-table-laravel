<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint for Flutter connectivity
     */
    public function check(): JsonResponse
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            return response()->json([
                'success' => true,
                'message' => 'API is healthy',
                'timestamp' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'status' => 'ok'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API health check failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Simple ping endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'pong',
            'timestamp' => now()->toISOString()
        ]);
    }
}
