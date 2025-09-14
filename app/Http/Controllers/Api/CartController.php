<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ManagesStock;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    use ManagesStock;

    /**
     * Validate cart items untuk stock availability
     */
    public function validateCart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'table_name' => 'nullable|string'
        ]);

        try {
            $items = collect($validated['items'])->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ];
            })->toArray();

            $errors = $this->validateStockAvailability($items);
            
            return response()->json([
                'is_valid' => empty($errors),
                'errors' => $errors,
                'items_count' => count($items),
                'total_quantity' => collect($items)->sum('quantity'),
                'validated_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Cart validation failed', [
                'items' => $validated['items'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'is_valid' => false,
                'errors' => ['Cart validation failed: ' . $e->getMessage()],
                'items_count' => 0,
                'total_quantity' => 0
            ], 500);
        }
    }

    /**
     * Get real-time stock for cart items
     */
    public function getCartStock(Request $request)
    {
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return response()->json([
                'message' => 'Product IDs are required',
                'data' => []
            ], 400);
        }

        try {
            $stockInfo = $this->getRealtimeStock($productIds);
            
            return response()->json([
                'message' => 'Cart stock information retrieved',
                'data' => $stockInfo,
                'checked_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cart stock', [
                'product_ids' => $productIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve cart stock',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Pre-checkout validation
     */
    public function preCheckout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|in:cash,qris,gopay',
            'table_name' => 'nullable|string'
        ]);

        try {
            $items = collect($validated['items'])->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ];
            })->toArray();

            // Validate stock availability
            $stockErrors = $this->validateStockAvailability($items);
            
            // Get current stock info
            $productIds = collect($items)->pluck('product_id')->unique()->toArray();
            $stockInfo = $this->getRealtimeStock($productIds);
            
            $canProceed = empty($stockErrors);
            
            return response()->json([
                'can_proceed' => $canProceed,
                'stock_errors' => $stockErrors,
                'stock_info' => $stockInfo,
                'items_summary' => [
                    'total_items' => count($items),
                    'total_quantity' => collect($items)->sum('quantity'),
                    'unique_products' => count($productIds)
                ],
                'payment_method' => $validated['payment_method'] ?? 'qris',
                'validated_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Pre-checkout validation failed', [
                'items' => $validated['items'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'can_proceed' => false,
                'stock_errors' => ['Pre-checkout validation failed: ' . $e->getMessage()],
                'stock_info' => [],
                'items_summary' => []
            ], 500);
        }
    }
}
