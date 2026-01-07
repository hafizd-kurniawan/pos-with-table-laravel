<?php

namespace Database\Seeders;

use App\Models\TableCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Regular',
                'icon' => '🪑',
                'color' => '#6366f1',
                'description' => 'Meja standar untuk pelanggan reguler',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'VIP',
                'icon' => '👑',
                'color' => '#f59e0b',
                'description' => 'Meja VIP dengan layanan khusus',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Private',
                'icon' => '🚪',
                'color' => '#8b5cf6',
                'description' => 'Meja privat dengan ruangan terpisah',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Outdoor',
                'icon' => '🌿',
                'color' => '#10b981',
                'description' => 'Meja outdoor/taman',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Bar',
                'icon' => '🍻',
                'color' => '#f97316',
                'description' => 'Meja bar counter',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Family',
                'icon' => '👨‍👩‍👧‍👦',
                'color' => '#06b6d4',
                'description' => 'Meja keluarga berkapasitas besar',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Couple',
                'icon' => '💕',
                'color' => '#ec4899',
                'description' => 'Meja romantis untuk pasangan',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Group',
                'icon' => '👥',
                'color' => '#64748b',
                'description' => 'Meja grup untuk rombongan',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            TableCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
