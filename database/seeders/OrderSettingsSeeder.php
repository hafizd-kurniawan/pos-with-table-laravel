<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default order settings using direct DB insert
     */
    public function run(): void
    {
        // Check if settings already exist
        $existingCount = DB::table('settings')
            ->whereIn('key', ['enable_discount', 'enable_tax', 'enable_service_charge'])
            ->count();

        if ($existingCount > 0) {
            echo "⚠️  Order settings already exist! Updating values...\n";
            
            // Update existing
            DB::table('settings')
                ->where('key', 'enable_discount')
                ->update(['value' => '1']);
            
            DB::table('settings')
                ->where('key', 'enable_tax')
                ->update(['value' => '1']);
            
            DB::table('settings')
                ->where('key', 'enable_service_charge')
                ->update(['value' => '1']);
                
            echo "✅ Order settings updated!\n";
            return;
        }

        // Get tenant_id (usually 1 for single tenant or first tenant)
        $tenantId = DB::table('tenants')->value('id') ?? 1;

        $settings = [
            [
                'tenant_id' => $tenantId,
                'key' => 'enable_discount',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'order',
                'label' => 'Enable Discount',
                'description' => 'Enable or disable discount feature in POS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $tenantId,
                'key' => 'enable_tax',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'order',
                'label' => 'Enable Tax',
                'description' => 'Enable or disable tax feature in POS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $tenantId,
                'key' => 'enable_service_charge',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'order',
                'label' => 'Enable Service Charge',
                'description' => 'Enable or disable service charge feature in POS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('settings')->insert($settings);

        echo "✅ Order settings created for tenant_id: {$tenantId}\n";
        echo "   - enable_discount: 1 (ON)\n";
        echo "   - enable_tax: 1 (ON)\n";
        echo "   - enable_service_charge: 1 (ON)\n";
        echo "\n🎉 You can now change these settings at /order-settings page!\n";
    }
}
