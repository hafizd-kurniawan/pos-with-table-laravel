<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Traits\ManagesStock;

class StockController extends Controller
{
    use ManagesStock;

    /**
     * Get real-time stock information for multiple products
     */
    public function checkStock(Request $request)
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
                'message' => 'Stock information retrieved successfully',
                'data' => $stockInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve stock information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate if items can be ordered (stock availability)
     */
    public function validateOrder(Request $request)
    {
        $items = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ])['items'];

        try {
            $errors = $this->validateStockAvailability($items);
            
            return response()->json([
                'is_valid' => empty($errors),
                'errors' => $errors,
                'message' => empty($errors) ? 'Order can be processed' : 'Order cannot be processed due to stock issues'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'is_valid' => false,
                'message' => 'Failed to validate order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current stock for a single product
     */
    public function getProductStock($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            return response()->json([
                'product_id' => $product->id,
                'name' => $product->name,
                'current_stock' => $product->stock,
                'is_available' => $product->isAvailable(),
                'status' => $product->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Bulk stock update (untuk admin/management)
     */
    public function updateStock(Request $request)
    {
        $updates = $request->validate([
            'updates' => 'required|array',
            'updates.*.product_id' => 'required|integer|exists:products,id',
            'updates.*.stock' => 'required|integer|min:0',
            'updates.*.operation' => 'required|in:set,add,subtract'
        ])['updates'];

        try {
            $results = [];
            
            foreach ($updates as $update) {
                $product = Product::findOrFail($update['product_id']);
                $oldStock = $product->stock;
                
                switch ($update['operation']) {
                    case 'set':
                        $product->stock = $update['stock'];
                        break;
                    case 'add':
                        $product->stock += $update['stock'];
                        break;
                    case 'subtract':
                        $product->stock = max(0, $product->stock - $update['stock']);
                        break;
                }
                
                $product->save();
                
                $results[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $product->stock,
                    'operation' => $update['operation']
                ];
            }
            
            return response()->json([
                'message' => 'Stock updated successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
