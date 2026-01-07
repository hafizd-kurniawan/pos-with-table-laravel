<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            ['name' => 'Beverages', 'description' => 'Drinks and refreshments', 'image' => 'beverages.jpg'],
            ['name' => 'Snacks', 'description' => 'Light snacks and appetizers', 'image' => 'snacks.jpg'],
            ['name' => 'Main Course', 'description' => 'Hearty main dishes', 'image' => 'main_course.jpg'],
            ['name' => 'Desserts', 'description' => 'Sweet treats and desserts', 'image' => 'desserts.jpg'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
