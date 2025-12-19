<?php

namespace App\Observers;

use App\Models\ProductAddon;

class ProductAddonObserver
{
    /**
     * Handle the ProductAddon "saving" event.
     * We use saving to update cost BEFORE it's written to DB if possible, 
     * but syncCost does an update(), so "saved" is safer to avoid loops if handled carefully.
     * Actually, let's use "saved" and check isDirty to avoid infinite loops.
     */
    public function saved(ProductAddon $addon): void
    {
        if ($addon->isDirty(['ingredient_id', 'quantity_needed'])) {
            $addon->syncCost();
        }
    }
}
