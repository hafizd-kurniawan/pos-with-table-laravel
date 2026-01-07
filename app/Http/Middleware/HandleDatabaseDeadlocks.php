<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class HandleDatabaseDeadlocks
{
    /**
     * Handle deadlock exceptions and retry with backoff
     */
    public function handle(Request $request, Closure $next)
    {
        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                return $next($request);
            } catch (QueryException $e) {
                // Check if it's a deadlock error
                if ($this->isDeadlock($e)) {
                    $retryCount++;
                    
                    Log::warning('Database deadlock detected, retrying...', [
                        'attempt' => $retryCount,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                        'url' => $request->url(),
                    ]);
                    
                    if ($retryCount >= $maxRetries) {
                        Log::error('Max deadlock retries exceeded', [
                            'attempts' => $retryCount,
                            'error' => $e->getMessage(),
                            'url' => $request->url(),
                        ]);
                        
                        return response()->json([
                            'message' => 'Service temporarily unavailable due to high traffic. Please try again.',
                            'error_code' => 'DEADLOCK_EXCEEDED'
                        ], 503);
                    }
                    
                    // Exponential backoff: wait 50ms, 100ms, 200ms
                    usleep(50000 * pow(2, $retryCount - 1));
                    continue;
                }
                
                // Re-throw if not a deadlock
                throw $e;
            }
        }
    }
    
    /**
     * Check if the exception is a deadlock
     */
    private function isDeadlock(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'deadlock') ||
               str_contains($e->getMessage(), 'Lock wait timeout') ||
               $e->getCode() === '40001' || // ISO SQL standard deadlock code
               $e->getCode() === 1213;     // MySQL deadlock code
    }
}
