<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;
use App\Models\TableCategory;
use App\Models\Tenant;

class TakeawayTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('⚠️ No tenants found. Skipping Takeaway table seeding.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("Creating Takeaway table for tenant: {$tenant->name}");

            // 1. Ensure 'Takeaway' Category exists
            $category = TableCategory::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Takeaway'
                ],
                [
                    'icon' => 'shopping-bag',
                    'color' => '#000000',
                    'description' => 'Special category for takeaway orders',
                    'sort_order' => 999 // Put at the end
                ]
            );

            // 2. Create 'Takeaway' Table
            Table::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Takeaway'
                ],
                [
                    'category_id' => $category->id,
                    'capacity' => 1,
                    'status' => 'available',
                    'location' => 'System',
                    'description' => 'System table for takeaway orders',
                    'position_x' => 0,
                    'position_y' => 0
                ]
            );
        }

        $this->command->info('✅ Takeaway tables seeded successfully!');
    }
}
