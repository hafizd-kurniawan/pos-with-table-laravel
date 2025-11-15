<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    /**
     * Get recipes for a product
     */
    public function index($productId)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $product = Product::where('tenant_id', $tenantId)
            ->with(['recipes.ingredient'])
            ->findOrFail($productId);
        
        $cogs = $product->calculateCOGS();
        
        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'cogs' => $cogs,
                    'profit' => $product->price - $cogs,
                    'profit_margin' => $product->price > 0 ? (($product->price - $cogs) / $product->price * 100) : 0,
                ],
                'recipes' => $product->recipes->map(function($recipe) {
                    return [
                        'id' => $recipe->id,
                        'ingredient' => [
                            'id' => $recipe->ingredient->id,
                            'name' => $recipe->ingredient->name,
                            'sku' => $recipe->ingredient->sku,
                            'unit' => $recipe->ingredient->unit,
                            'cost_per_unit' => $recipe->ingredient->cost_per_unit,
                            'current_stock' => $recipe->ingredient->current_stock,
                        ],
                        'quantity_needed' => $recipe->quantity_needed,
                        'cost' => $recipe->cost,
                        'notes' => $recipe->notes,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Add ingredient to recipe
     */
    public function store(Request $request, $productId)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity_needed' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string',
        ]);
        
        // Check if recipe already exists
        $existing = Recipe::where('product_id', $productId)
            ->where('ingredient_id', $validated['ingredient_id'])
            ->first();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient already exists in this recipe',
            ], 422);
        }
        
        $recipe = Recipe::create([
            'product_id' => $productId,
            'ingredient_id' => $validated['ingredient_id'],
            'quantity_needed' => $validated['quantity_needed'],
            'notes' => $validated['notes'] ?? null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Ingredient added to recipe',
            'data' => $recipe->load('ingredient'),
        ], 201);
    }

    /**
     * Update recipe
     */
    public function update(Request $request, $productId, $recipeId)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        $recipe = Recipe::where('product_id', $productId)->findOrFail($recipeId);
        
        $validated = $request->validate([
            'quantity_needed' => 'sometimes|numeric|min:0.001',
            'notes' => 'nullable|string',
        ]);
        
        $recipe->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Recipe updated',
            'data' => $recipe->fresh('ingredient'),
        ]);
    }

    /**
     * Remove ingredient from recipe
     */
    public function destroy($productId, $recipeId)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        $recipe = Recipe::where('product_id', $productId)->findOrFail($recipeId);
        
        $recipe->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Ingredient removed from recipe',
        ]);
    }

    /**
     * Check if product can be produced
     */
    public function checkAvailability($productId, Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        
        $quantity = $request->input('quantity', 1);
        
        $result = $product->canBeProduced($quantity);
        $maxQuantity = $product->getMaxProducibleQuantity();
        
        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $productId,
                'requested_quantity' => $quantity,
                'can_produce' => $result['can_produce'],
                'max_producible_quantity' => $maxQuantity,
                'insufficient_ingredients' => $result['insufficient_ingredients'],
            ],
        ]);
    }
}
