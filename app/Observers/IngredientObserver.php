<?php

namespace App\Observers;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class IngredientObserver
{
    /**
     * Handle the Ingredient "saved" event.
     * This covers both created and updated events.
     */
    public function saved(Ingredient $ingredient): void
    {
        // Check if relevant fields changed (stock or cost)
        if ($ingredient->isDirty(['current_stock', 'cost_per_unit'])) {
            Log::info("Ingredient {$ingredient->name} changed. Syncing related products...");
            
            // Find all products that use this ingredient
            $products = Product::whereHas('recipes', function ($query) use ($ingredient) {
                $query->where('ingredient_id', $ingredient->id);
            })->get();

            foreach ($products as $product) {
                $product->syncStockAndCost();
            }

            // Also sync related ProductAddons
            $addons = \App\Models\ProductAddon::where('ingredient_id', $ingredient->id)->get();
            foreach ($addons as $addon) {
                $addon->syncCost();
            }
        }
    }

    /**
     * Handle the Ingredient "deleted" event.
     */
    public function deleted(Ingredient $ingredient): void
    {
        // If ingredient is deleted, we should probably re-sync too
        // although usually we soft-delete
        $products = Product::whereHas('recipes', function ($query) use ($ingredient) {
            $query->where('ingredient_id', $ingredient->id);
        })->get();

        foreach ($products as $product) {
            $product->syncStockAndCost();
        }
    }
}
