<?php

namespace App\Traits;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ManagesStock
{
    /**
     * Safely decrease product stock with concurrency protection
     */
    public function safelyDecreaseStock(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            // Lock semua products yang akan dikurangi stocknya
            $productIds = collect($items)->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validasi dan kurangi stock
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock}, Required: {$item['quantity']}");
                }
                
                // Kurangi stock
                $product->decrement('stock', $item['quantity']);
                
                Log::info('Stock decreased', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_decreased' => $item['quantity'],
                    'remaining_stock' => $product->fresh()->stock
                ]);
            }
            
            return true;
        }, 5); // Retry 5 kali jika deadlock
    }

    /**
     * Safely increase product stock (untuk refund/cancel)
     */
    public function safelyIncreaseStock(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            // Lock semua products yang akan ditambah stocknya
            $productIds = collect($items)->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Tambah stock
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }
                
                // Tambah stock
                $product->increment('stock', $item['quantity']);
                
                Log::info('Stock increased', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_increased' => $item['quantity'],
                    'new_stock' => $product->fresh()->stock
                ]);
            }
            
            return true;
        }, 5); // Retry 5 kali jika deadlock
    }

    /**
     * Validate stock availability for multiple items
     */
    public function validateStockAvailability(array $items): array
    {
        return DB::transaction(function () use ($items) {
            $productIds = collect($items)->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate() // Lock untuk memastikan data konsisten
                ->get()
                ->keyBy('id');

            $errors = [];
            
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                
                if (!$product) {
                    $errors[] = "Product with ID {$item['product_id']} not found";
                    continue;
                }
                
                if (!$product->isAvailable()) {
                    $errors[] = "Product '{$product->name}' is not available";
                    continue;
                }
                
                if ($product->stock < $item['quantity']) {
                    $errors[] = "Insufficient stock for '{$product->name}'. Available: {$product->stock}, Requested: {$item['quantity']}";
                }
            }
            
            return $errors;
        }, 5);
    }

    /**
     * Reserve stock for pending orders (untuk QRIS/GoPay)
     */
    public function reserveStock(Order $order): bool
    {
        $items = $order->orderItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity
            ];
        })->toArray();

        return $this->safelyDecreaseStock($items);
    }

    /**
     * Release reserved stock (untuk cancelled orders)
     */
    public function releaseStock(Order $order): bool
    {
        $items = $order->orderItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity
            ];
        })->toArray();

        return $this->safelyIncreaseStock($items);
    }

    /**
     * Check real-time stock untuk frontend
     */
    public function getRealtimeStock(array $productIds): array
    {
        return Product::whereIn('id', $productIds)
            ->select('id', 'name', 'stock', 'status')
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'available_stock' => $product->stock,
                    'is_available' => $product->isAvailable(),
                    'status' => $product->status
                ];
            })
            ->toArray();
    }
}
