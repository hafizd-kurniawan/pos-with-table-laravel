<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\Tenant;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants or create default if none
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            // Fallback if no tenants exist yet (should not happen in prod)
            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedUnitsForTenant($tenant->id);
        }
    }

    private function seedUnitsForTenant($tenantId)
    {
        $units = [
            // Weight
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'type' => 'weight',
                'sort_order' => 1,
                'description' => 'Metric unit of mass equal to 1000 grams',
            ],
            [
                'name' => 'Gram',
                'symbol' => 'g',
                'type' => 'weight',
                'sort_order' => 2,
                'description' => 'Metric unit of mass',
            ],
            [
                'name' => 'Milligram',
                'symbol' => 'mg',
                'type' => 'weight',
                'sort_order' => 3,
                'description' => 'Metric unit of mass equal to 1/1000 gram',
            ],
            
            // Volume
            [
                'name' => 'Liter',
                'symbol' => 'l',
                'type' => 'volume',
                'sort_order' => 4,
                'description' => 'Metric unit of volume',
            ],
            [
                'name' => 'Milliliter',
                'symbol' => 'ml',
                'type' => 'volume',
                'sort_order' => 5,
                'description' => 'Metric unit of volume equal to 1/1000 liter',
            ],
            
            // Count/Pieces
            [
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'type' => 'count',
                'sort_order' => 6,
                'description' => 'Standard counting unit',
            ],
            [
                'name' => 'Pack',
                'symbol' => 'pck',
                'type' => 'count',
                'sort_order' => 7,
                'description' => 'Standard packing unit',
            ],
            [
                'name' => 'Box',
                'symbol' => 'box',
                'type' => 'count',
                'sort_order' => 8,
                'description' => 'Box container',
            ],
            [
                'name' => 'Dozen',
                'symbol' => 'doz',
                'type' => 'count',
                'sort_order' => 9,
                'description' => 'Set of 12 items',
            ],
            
            // General/Kitchen Specific
            [
                'name' => 'Portion',
                'symbol' => 'por',
                'type' => 'general',
                'sort_order' => 10,
                'description' => 'Serving portion',
            ],
            [
                'name' => 'Cup',
                'symbol' => 'cup',
                'type' => 'volume',
                'sort_order' => 11,
                'description' => 'Standard cup measurement',
            ],
            [
                'name' => 'Tablespoon',
                'symbol' => 'tbsp',
                'type' => 'volume',
                'sort_order' => 12,
                'description' => 'Tablespoon measurement',
            ],
            [
                'name' => 'Teaspoon',
                'symbol' => 'tsp',
                'type' => 'volume',
                'sort_order' => 13,
                'description' => 'Teaspoon measurement',
            ],
        ];

        foreach ($units as $unitData) {
            Unit::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'symbol' => $unitData['symbol']
                ],
                [
                    'name' => $unitData['name'],
                    'type' => $unitData['type'],
                    'sort_order' => $unitData['sort_order'],
                    'description' => $unitData['description'],
                    'status' => 'active',
                ]
            );
        }
    }
}
