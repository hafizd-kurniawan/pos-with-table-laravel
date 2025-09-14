<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransactionController extends Controller
{
    /**
     * Display stock transaction history
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('stock_transactions')
                  ->join('products', 'stock_transactions.product_id', '=', 'products.id')
                  ->select([
                      'stock_transactions.*',
                      'products.name as product_name',
                      'products.barcode'
                  ])
                  ->orderBy('stock_transactions.created_at', 'desc');

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('stock_transactions.product_id', $request->product_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('stock_transactions.type', $request->type);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('stock_transactions.created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('stock_transactions.created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'Stock Transaction History',
            'data' => $transactions
        ]);
    }

    /**
     * Store a stock transaction (for Flutter sync)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $product = Product::lockForUpdate()->find($request->product_id);

            // Check stock for 'out' transactions
            if ($request->type === 'out' && $product->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock
                ], 422);
            }

            // Create stock transaction record (simulate)
            $transaction = [
                'id' => DB::table('stock_transactions')->insertGetId([
                    'product_id' => $request->product_id,
                    'type' => $request->type,
                    'quantity' => $request->quantity,
                    'notes' => $request->notes,
                    'previous_stock' => $product->stock,
                    'new_stock' => $request->type === 'in' 
                        ? $product->stock + $request->quantity
                        : $product->stock - $request->quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]) ?: time(), // Fallback ID if table doesn't exist yet
                'product_id' => $request->product_id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'notes' => $request->notes,
                'previous_stock' => $product->stock,
                'created_at' => now()
            ];

            // Update product stock
            if ($request->type === 'in') {
                $product->increaseStock($request->quantity);
            } else {
                $product->decreaseStock($request->quantity);
            }

            $transaction['new_stock'] = $product->fresh()->stock;
            $transaction['product'] = $product->fresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock Transaction Created',
                'data' => $transaction
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock transaction failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Stock transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch stock transactions (for Flutter offline sync)
     */
    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'transactions' => 'required|array',
            'transactions.*.product_id' => 'required|exists:products,id',
            'transactions.*.type' => 'required|in:in,out',
            'transactions.*.quantity' => 'required|integer|min:1',
            'transactions.*.notes' => 'nullable|string',
            'transactions.*.timestamp' => 'required|date'
        ]);

        $results = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($request->transactions as $index => $transactionData) {
                try {
                    $product = Product::lockForUpdate()->find($transactionData['product_id']);

                    // Check stock for 'out' transactions
                    if ($transactionData['type'] === 'out' && $product->stock < $transactionData['quantity']) {
                        $errors[] = [
                            'index' => $index,
                            'message' => "Insufficient stock for product {$product->name}. Available: {$product->stock}"
                        ];
                        continue;
                    }

                    // Create transaction record
                    $transactionId = time() . $index; // Simulated ID
                    
                    // Update product stock
                    if ($transactionData['type'] === 'in') {
                        $product->increaseStock($transactionData['quantity']);
                    } else {
                        $product->decreaseStock($transactionData['quantity']);
                    }

                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'transaction_id' => $transactionId,
                        'product_id' => $transactionData['product_id'],
                        'new_stock' => $product->fresh()->stock
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }

            if (empty($errors)) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            return response()->json([
                'success' => empty($errors),
                'message' => empty($errors) 
                    ? 'All transactions processed successfully'
                    : 'Some transactions failed',
                'data' => [
                    'processed' => $results,
                    'errors' => $errors,
                    'total' => count($request->transactions),
                    'success_count' => count($results),
                    'error_count' => count($errors)
                ]
            ], empty($errors) ? 200 : 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch stock transaction failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Batch processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current stock levels for all products
     */
    public function stockLevels(): JsonResponse
    {
        $products = Product::select(['id', 'name', 'barcode', 'stock', 'status'])
                          ->orderBy('name')
                          ->get();

        return response()->json([
            'success' => true,
            'message' => 'Current Stock Levels',
            'data' => $products
        ]);
    }
}
