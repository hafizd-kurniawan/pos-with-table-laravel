<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Traits\ManagesStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderManagementController extends Controller
{
    use ManagesStock;

    /**
     * Release expired orders manually
     */
    public function releaseExpiredOrders()
    {
        try {
            // Cari orders yang pending lebih dari 2 menit
            // Use withoutGlobalScope('tenant') untuk admin access
            $expiredOrders = Order::withoutGlobalScope('tenant')
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subMinutes(2))
                ->with('orderItems.product')
                ->get();

            $releasedCount = 0;

            foreach ($expiredOrders as $order) {
                try {
                    // Release stock kembali
                    $this->releaseStock($order);
                    
                    // Update status order
                    $order->status = 'expired';
                    $order->expired_at = now();
                    $order->save();

                    $releasedCount++;
                    
                    Log::info('Order expired and stock released via web', [
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'table' => $order->table->name ?? 'Unknown'
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to release expired order stock via web', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Released {$releasedCount} expired orders",
                'released_count' => $releasedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in release expired orders endpoint', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to release expired orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order status for AJAX checking
     */
    public function checkOrderStatus(Request $request, $code)
    {
        try {
            // Bypass tenant scope for public status check
            $order = Order::withoutGlobalScope('tenant')
                ->where('code', $code)
                ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if order should be expired (2 minutes)
            if ($order->status === 'pending' && $order->created_at->diffInMinutes(now()) > 2) {
                // Auto-expire this order
                try {
                    $this->releaseStock($order);
                    $order->status = 'expired';
                    $order->expired_at = now();
                    $order->save();
                    
                    Log::info('Order auto-expired during status check', [
                        'order_code' => $order->code
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to auto-expire order', [
                        'order_code' => $order->code,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'code' => $order->code,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'is_expired' => $order->isExpired(),
                    'minutes_elapsed' => $order->created_at->diffInMinutes(now())
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking order status: ' . $e->getMessage()
            ], 500);
        }
    }
}
