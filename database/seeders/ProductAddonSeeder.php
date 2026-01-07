<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAddon;

class ProductAddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::where('status', 'available')->get();

        foreach ($products as $product) {
            // Add random addons to some products
            if (rand(0, 1)) {
                ProductAddon::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'name' => 'Extra Shot',
                    'price' => 5000,
                    'is_available' => true,
                ]);

                ProductAddon::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'name' => 'Large Size',
                    'price' => 3000,
                    'is_available' => true,
                ]);
                
                ProductAddon::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'name' => 'Topping Keju',
                    'price' => 4000,
                    'is_available' => true,
                ]);
            }
        }
    }
}
