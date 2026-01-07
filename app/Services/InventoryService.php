<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Manually adjust stock
     */
    public function adjustStock(int $ingredientId, float $quantity, string $reason, int $userId): array
    {
        return DB::transaction(function() use ($ingredientId, $quantity, $reason, $userId) {
            $ingredient = Ingredient::lockForUpdate()->findOrFail($ingredientId);
            
            $oldStock = $ingredient->current_stock;
            $newStock = $oldStock + $quantity;
            
            // Business rule: Stock cannot be negative
            if ($newStock < 0) {
                throw new \Exception("Stock cannot be negative. Current: {$oldStock}, Adjustment: {$quantity}");
            }
            
            // Update stock
            $ingredient->updateStock($newStock);
            
            // Create movement record
            $movement = StockMovement::create([
                'tenant_id' => $ingredient->tenant_id,
                'ingredient_id' => $ingredientId,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => abs($quantity),
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'user_id' => $userId,
                'notes' => $reason,
                'moved_at' => now(),
            ]);
            
            // Check low stock
            if ($ingredient->isLowStock()) {
                event(new \App\Events\LowStockDetected($ingredient));
            }
            
            return [
                'success' => true,
                'ingredient' => $ingredient->fresh(),
                'movement' => $movement,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ];
        });
    }

    /**
     * Deduct stock when order is completed (recipe-based)
     */
    /**
     * Deduct stock when order is completed (recipe-based)
     * ATOMIC: Fails if ANY ingredient is insufficient.
     */
    public function deductStockForOrder(int $orderId): array
    {
        $order = Order::with('orderItems.product.recipes.ingredient')->findOrFail($orderId);
        
        return DB::transaction(function() use ($order) {
            $deductions = [];
            $insufficientStock = [];
            $requirements = []; // [ingredient_id => quantity_needed]
            
            // 1. Calculate Total Requirements
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                
                // Ensure recipes are loaded
                if (!$product->relationLoaded('recipes')) {
                    $product->load('recipes.ingredient');
                }

                // Skip if product has no recipes
                if ($product->recipes->isEmpty()) {
                    continue;
                }
                
                foreach ($product->recipes as $recipe) {
                    $needed = $recipe->quantity_needed * $orderItem->quantity;
                    $ingredientId = $recipe->ingredient_id;
                    
                    if (!isset($requirements[$ingredientId])) {
                        $requirements[$ingredientId] = 0;
                    }
                    $requirements[$ingredientId] += $needed;
                }

                // Process Addons Stock Requirements
                if ($orderItem->addons && $orderItem->addons->count() > 0) {
                    foreach ($orderItem->addons as $itemAddon) {
                        $productAddon = \App\Models\ProductAddon::find($itemAddon->product_addon_id);
                        
                        if ($productAddon && $productAddon->ingredient_id && $productAddon->quantity_needed > 0) {
                            $needed = $productAddon->quantity_needed * $orderItem->quantity;
                            $ingredientId = $productAddon->ingredient_id;

                            if (!isset($requirements[$ingredientId])) {
                                $requirements[$ingredientId] = 0;
                            }
                            $requirements[$ingredientId] += $needed;
                        }
                    }
                }
            }

            // 2. Validate Stock Availability (Locking Rows)
            if (!empty($requirements)) {
                $ingredients = Ingredient::whereIn('id', array_keys($requirements))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($requirements as $ingredientId => $needed) {
                    $ingredient = $ingredients->get($ingredientId);

                    if (!$ingredient) {
                        throw new \Exception("Ingredient ID {$ingredientId} not found.");
                    }

                    if ($ingredient->current_stock < $needed) {
                        $insufficientStock[] = [
                            'ingredient' => $ingredient->name,
                            'needed' => $needed,
                            'available' => $ingredient->current_stock,
                        ];
                    }
                }
            }

            // 3. Fail if Insufficient
            if (!empty($insufficientStock)) {
                $message = "Insufficient stock for ingredients: " . collect($insufficientStock)->pluck('ingredient')->join(', ');
                Log::warning("Order #{$order->order_number} failed due to insufficient stock", ['issues' => $insufficientStock]);
                throw new \Exception($message);
            }

            // 4. Deduct Stock
            $lowStockIngredients = [];
            
            if (!empty($requirements)) {
                foreach ($requirements as $ingredientId => $needed) {
                    $ingredient = $ingredients->get($ingredientId);
                    $oldStock = $ingredient->current_stock;
                    $newStock = $oldStock - $needed;

                    $ingredient->updateStock($newStock);

                    // Handle null user_id
                    $userId = $order->user_id;
                    if (!$userId) {
                        $userId = \App\Models\User::where('tenant_id', $order->tenant_id)->value('id');
                    }

                    StockMovement::create([
                        'tenant_id' => $order->tenant_id,
                        'ingredient_id' => $ingredient->id,
                        'type' => StockMovement::TYPE_OUT,
                        'quantity' => $needed,
                        'stock_before' => $oldStock,
                        'stock_after' => $newStock,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'user_id' => $userId,
                        'notes' => "Used for Order #{$order->order_number}",
                        'moved_at' => now(),
                    ]);

                    $deductions[] = [
                        'ingredient' => $ingredient->name,
                        'quantity' => $needed,
                        'unit' => $ingredient->unit,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                    ];

                    if ($ingredient->fresh()->isLowStock()) {
                        $lowStockIngredients[] = $ingredient->fresh();
                        event(new \App\Events\LowStockDetected($ingredient->fresh()));
                    }
                }
            }
            
            return [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'deductions' => $deductions,
                'low_stock_alerts' => $lowStockIngredients,
            ];
        });
    }

    /**
     * Restore stock when order is cancelled (recipe-based)
     */
    public function restoreStockForOrder(int $orderId): array
    {
        $order = Order::with('orderItems.product.recipes.ingredient')->findOrFail($orderId);
        
        return DB::transaction(function() use ($order) {
            $restorations = [];
            
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                
                // Ensure recipes are loaded
                if (!$product->relationLoaded('recipes')) {
                    $product->load('recipes.ingredient');
                }

                // Skip if product has no recipes
                if ($product->recipes->isEmpty()) {
                    continue;
                }
                
                foreach ($product->recipes as $recipe) {
                    $needed = $recipe->quantity_needed * $orderItem->quantity;
                    $ingredient = $recipe->ingredient;
                    
                    $oldStock = $ingredient->current_stock;
                    $newStock = $oldStock + $needed;
                    
                    // Update stock
                    $ingredient->updateStock($newStock);
                    
                    // Create movement record
                    // Handle null user_id (e.g. Self Order)
                    $userId = $order->user_id;
                    if (!$userId) {
                        // Fallback: Use first user of the tenant (usually admin)
                        $userId = \App\Models\User::where('tenant_id', $order->tenant_id)->value('id');
                    }

                    StockMovement::create([
                        'tenant_id' => $order->tenant_id,
                        'ingredient_id' => $ingredient->id,
                        'type' => StockMovement::TYPE_IN, // Restocking
                        'quantity' => $needed,
                        'stock_before' => $oldStock,
                        'stock_after' => $newStock,
                        'reference_type' => 'order_cancellation',
                        'reference_id' => $order->id,
                        'user_id' => $userId, // Fixed: Cannot be null
                        'notes' => "Restored from Order #{$order->order_number} - {$product->name} x{$orderItem->quantity}",
                        'moved_at' => now(),
                    ]);
                    
                    $restorations[] = [
                        'ingredient' => $ingredient->name,
                        'quantity' => $needed,
                        'unit' => $ingredient->unit,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                    ];
                }
            }
            
            return [
                'success' => true,
                'order_id' => $order->id,
                'restorations' => $restorations,
            ];
        });
    }

    /**
     * Check all low stock ingredients
     */
    public function checkLowStock(int $tenantId): array
    {
        $lowStockIngredients = Ingredient::where('tenant_id', $tenantId)
            ->active()
            ->lowStock()
            ->with('supplier')
            ->get();
        
        return [
            'count' => $lowStockIngredients->count(),
            'ingredients' => $lowStockIngredients->map(function($ingredient) {
                return [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'sku' => $ingredient->sku,
                    'current_stock' => $ingredient->current_stock,
                    'min_stock' => $ingredient->min_stock,
                    'unit' => $ingredient->unit,
                    'status' => $ingredient->stock_status,
                    'supplier' => [
                        'id' => $ingredient->supplier?->id,
                        'name' => $ingredient->supplier?->name,
                        'phone' => $ingredient->supplier?->phone,
                    ],
                ];
            }),
        ];
    }

    /**
     * Get stock movement history
     */
    public function getStockHistory(int $ingredientId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = StockMovement::where('ingredient_id', $ingredientId)
            ->with(['user', 'ingredient'])
            ->orderBy('moved_at', 'desc');
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        $movements = $query->get();
        
        return [
            'ingredient_id' => $ingredientId,
            'total_movements' => $movements->count(),
            'movements' => $movements->map(function($movement) {
                return [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'type_label' => $movement->type_label,
                    'quantity' => $movement->quantity,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'reference_type' => $movement->reference_type,
                    'reference_id' => $movement->reference_id,
                    'user' => $movement->user->name,
                    'notes' => $movement->notes,
                    'moved_at' => $movement->moved_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }

    /**
     * Calculate total inventory value
     */
    public function calculateInventoryValue(int $tenantId): array
    {
        $ingredients = Ingredient::where('tenant_id', $tenantId)
            ->active()
            ->get();
        
        $totalValue = $ingredients->sum('stock_value');
        
        return [
            'total_ingredients' => $ingredients->count(),
            'total_value' => $totalValue,
            'total_value_formatted' => 'Rp ' . number_format($totalValue, 0, ',', '.'),
            'by_category' => $ingredients->groupBy('category')->map(function($items, $category) {
                return [
                    'category' => $category ?: 'Uncategorized',
                    'count' => $items->count(),
                    'value' => $items->sum('stock_value'),
                ];
            })->values(),
        ];
    }

    /**
     * Get ingredients that will expire soon (if we add expiry tracking later)
     */
    public function getExpiringIngredients(int $tenantId, int $daysThreshold = 7): array
    {
        // Placeholder for future enhancement
        return [
            'message' => 'Expiry tracking not yet implemented',
            'count' => 0,
            'ingredients' => [],
        ];
    }

    /**
     * Validate if order can be fulfilled with current stock
     */
    public function validateOrderStock(Order $order): array
    {
        $canFulfill = true;
        $issues = [];
        
        foreach ($order->orderItems as $orderItem) {
            $product = $orderItem->product;
            
            if ($product->recipes->isEmpty()) {
                continue;
            }
            
            $check = $product->canBeProduced($orderItem->quantity);
            
            if (!$check['can_produce']) {
                $canFulfill = false;
                $issues[] = [
                    'product' => $product->name,
                    'quantity_ordered' => $orderItem->quantity,
                    'insufficient_ingredients' => $check['insufficient_ingredients'],
                ];
            }
        }
        
        return [
            'can_fulfill' => $canFulfill,
            'issues' => $issues,
        ];
    }
}
