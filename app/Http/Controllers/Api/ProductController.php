<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //index
    public function index()
    {
        // Hanya tampilkan produk yang available dan ada stock
        $products = \App\Models\Product::with('category')
            ->available() // menggunakan scope available
            ->get();
            
        // Tambahkan informasi stock dalam response
        $products = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'image' => $product->image_url,
                'stock' => $product->stock,
                'status' => $product->status,
                'is_available' => $product->isAvailable(),
                'category_id' => $product->category_id,
                'category' => $product->category,
                'is_featured' => $product->is_featured,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        });
            
        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ]);
    }

    // Check stock availability for multiple products
    public function checkStock(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $result = [];
        $allAvailable = true;

        foreach ($request->items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            
            $isAvailable = $product->isAvailable() && $product->stock >= $item['quantity'];
            
            if (!$isAvailable) {
                $allAvailable = false;
            }

            $result[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'requested_quantity' => $item['quantity'],
                'available_stock' => $product->stock,
                'status' => $product->status,
                'is_available' => $isAvailable,
                'message' => $isAvailable 
                    ? 'Stock available' 
                    : ($product->status !== 'available' 
                        ? 'Product not available' 
                        : "Insufficient stock. Available: {$product->stock}, Requested: {$item['quantity']}")
            ];
        }

        return response()->json([
            'all_available' => $allAvailable,
            'items' => $result,
        ]);
    }

    // Get single product with stock info
    public function show($id)
    {
        $product = \App\Models\Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'image' => $product->image_url,
                'stock' => $product->stock,
                'status' => $product->status,
                'is_available' => $product->isAvailable(),
                'category_id' => $product->category_id,
                'category' => $product->category,
                'is_featured' => $product->is_featured,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ]
        ]);
    }
}
