<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $products = [
            ['name' => 'Coca Cola', 'description' => 'Refreshing soft drink', 'price' => 1, 'category_id' => 1, 'image' => 'coca_cola.jpg'],
            ['name' => 'Chips', 'description' => 'Crunchy potato chips', 'price' => 2, 'category_id' => 2, 'image' => 'chips.jpg'],
            ['name' => 'Pizza', 'description' => 'Delicious cheese pizza', 'price' => 1, 'category_id' => 3, 'image' => 'pizza.jpg'],
            ['name' => 'Chocolate Cake', 'description' => 'Rich chocolate cake', 'price' => 2, 'category_id' => 4, 'image' => 'chocolate_cake.jpg'],
            ['name' => 'Iced Tea', 'description' => 'Cool and refreshing iced tea', 'price' => 3, 'category_id' => 1, 'image' => 'iced_tea.jpg'],
            ['name' => 'Nachos', 'description' => 'Crispy nachos with cheese', 'price' => 1, 'category_id' => 2, 'image' => 'nachos.jpg'],
            ['name' => 'Burger', 'description' => 'Juicy beef burger', 'price' => 1, 'category_id' => 3, 'image' => 'burger.jpg'],
            ['name' => 'Ice Cream', 'description' => 'Creamy vanilla ice cream', 'price' => 1, 'category_id' => 4, 'image' => 'ice_cream.jpg'],
        ];
        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
