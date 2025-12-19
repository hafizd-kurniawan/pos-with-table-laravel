<?php

namespace App\Observers;

use App\Models\Recipe;
use Illuminate\Support\Facades\Log;

class RecipeObserver
{
    /**
     * Handle the Recipe "saved" event.
     */
    public function saved(Recipe $recipe): void
    {
        Log::info("Recipe saved for Product ID: {$recipe->product_id}. Syncing...");
        $recipe->product->syncStockAndCost();
    }

    /**
     * Handle the Recipe "deleted" event.
     */
    public function deleted(Recipe $recipe): void
    {
        Log::info("Recipe deleted for Product ID: {$recipe->product_id}. Syncing...");
        $recipe->product->syncStockAndCost();
    }
}
