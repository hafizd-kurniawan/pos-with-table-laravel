<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IngredientController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get all ingredients
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = Ingredient::where('tenant_id', $tenantId)
            ->with('supplier');
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Filter by low stock
        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        $ingredients = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $ingredients->map(function($ingredient) {
                return [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'sku' => $ingredient->sku,
                    'unit' => $ingredient->unit,
                    'current_stock' => $ingredient->current_stock,
                    'min_stock' => $ingredient->min_stock,
                    'max_stock' => $ingredient->max_stock,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                    'stock_value' => $ingredient->stock_value,
                    'stock_value_formatted' => 'Rp ' . number_format($ingredient->stock_value, 0, ',', '.'),
                    'category' => $ingredient->category,
                    'status' => $ingredient->status,
                    'is_low_stock' => $ingredient->is_low_stock,
                    'stock_status' => $ingredient->stock_status,
                    'stock_status_color' => $ingredient->stock_status_color,
                    'image' => $ingredient->image,
                    'supplier' => $ingredient->supplier ? [
                        'id' => $ingredient->supplier->id,
                        'name' => $ingredient->supplier->name,
                        'phone' => $ingredient->supplier->phone,
                    ] : null,
                    // Pre-formatted for display
                    'display_name' => $ingredient->name . ' (' . $ingredient->sku . ')',
                    'stock_text' => "{$ingredient->current_stock} {$ingredient->unit}",
                ];
            }),
        ]);
    }

    /**
     * Get single ingredient
     */
    public function show($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $ingredient = Ingredient::where('tenant_id', $tenantId)
            ->with(['supplier', 'recipes.product'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'sku' => $ingredient->sku,
                'unit' => $ingredient->unit,
                'current_stock' => $ingredient->current_stock,
                'min_stock' => $ingredient->min_stock,
                'max_stock' => $ingredient->max_stock,
                'cost_per_unit' => $ingredient->cost_per_unit,
                'stock_value' => $ingredient->stock_value,
                'category' => $ingredient->category,
                'description' => $ingredient->description,
                'status' => $ingredient->status,
                'is_low_stock' => $ingredient->is_low_stock,
                'stock_status' => $ingredient->stock_status,
                'image' => $ingredient->image,
                'supplier' => $ingredient->supplier,
                'used_in_products' => $ingredient->recipes->map(function($recipe) {
                    return [
                        'product_id' => $recipe->product_id,
                        'product_name' => $recipe->product->name,
                        'quantity_needed' => $recipe->quantity_needed,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Create ingredient
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);
        
        $validated['tenant_id'] = $tenantId;
        $validated['sku'] = Ingredient::generateSKU($tenantId);
        $validated['current_stock'] = 0;
        $validated['status'] = 'active';
        
        $ingredient = Ingredient::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Ingredient created successfully',
            'data' => $ingredient,
        ], 201);
    }

    /**
     * Update ingredient
     */
    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $ingredient = Ingredient::where('tenant_id', $tenantId)->findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'unit' => 'sometimes|string|max:50',
            'min_stock' => 'sometimes|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'sometimes|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);
        
        $ingredient->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Ingredient updated successfully',
            'data' => $ingredient->fresh(),
        ]);
    }

    /**
     * Delete ingredient
     */
    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $ingredient = Ingredient::where('tenant_id', $tenantId)->findOrFail($id);
        
        // Check if used in any recipes
        if ($ingredient->recipes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ingredient that is used in recipes',
            ], 422);
        }
        
        $ingredient->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Ingredient deleted successfully',
        ]);
    }

    /**
     * Adjust stock manually
     */
    public function adjustStock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'reason' => 'required|string|max:255',
        ]);
        
        try {
            $result = $this->inventoryService->adjustStock(
                ingredientId: $id,
                quantity: $validated['quantity'],
                reason: $validated['reason'],
                userId: Auth::id()
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get stock movement history
     */
    public function stockHistory($id, Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $result = $this->inventoryService->getStockHistory($id, $startDate, $endDate);
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get low stock ingredients
     */
    public function lowStock()
    {
        $tenantId = Auth::user()->tenant_id;
        
        $result = $this->inventoryService->checkLowStock($tenantId);
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get categories
     */
    public function categories()
    {
        $tenantId = Auth::user()->tenant_id;
        
        $categories = Ingredient::where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');
        
        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
