<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * DefaultTenantSeeder
 * 
 * Created: 2025-11-13 04:26:00 WIB
 * Purpose: Create default tenant and assign tenant_id to all existing data
 */
class DefaultTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating default tenant...');
        
        // Create default tenant with 30 days trial
        $tenant = Tenant::updateOrCreate(
            ['subdomain' => 'default'],
            [
                'business_name' => 'Default Restaurant',
                'email' => 'admin@default.restaurant',
                'phone' => '081234567890',
                'address' => 'Jl. Default No. 123, Jakarta',
                'status' => 'trial',
                'trial_starts_at' => now(),
                'trial_ends_at' => now()->addDays(30), // 30 hari trial
            ]
        );
        
        $this->command->info("✅ Default tenant created: {$tenant->business_name} (ID: {$tenant->id})");
        $this->command->info("   Subdomain: {$tenant->subdomain}");
        $this->command->info("   Trial ends: {$tenant->trial_ends_at->format('d M Y')}");
        $this->command->newLine();
        
        // Assign tenant_id to all existing data
        $this->command->info('📦 Assigning tenant_id to existing data...');
        
        $tables = [
            'products',
            'categories',
            'orders',
            'order_items',
            'tables',
            'table_categories',
            'reservations',
            'discounts',
            'taxes',
            'settings',
            'users',
        ];
        
        $totalUpdated = 0;
        
        foreach ($tables as $table) {
            $query = DB::table($table)->whereNull('tenant_id');
            
            // Exclude Super Admin from users update
            if ($table === 'users') {
                $query->where('email', '!=', 'admin@possaas.com');
            }
            
            $count = $query->update(['tenant_id' => $tenant->id]);
            
            if ($count > 0) {
                $this->command->info("   ✓ {$table}: {$count} records assigned");
                $totalUpdated += $count;
            } else {
                $this->command->warn("   ⊘ {$table}: No records to assign");
            }
        }
        
        $this->command->newLine();
        $this->command->info("✅ Total: {$totalUpdated} records assigned to tenant ID: {$tenant->id}");
        $this->command->newLine();
        
        // Display summary
        $this->command->table(
            ['Table', 'Count'],
            [
                ['Products', DB::table('products')->where('tenant_id', $tenant->id)->count()],
                ['Categories', DB::table('categories')->where('tenant_id', $tenant->id)->count()],
                ['Orders', DB::table('orders')->where('tenant_id', $tenant->id)->count()],
                ['Tables', DB::table('tables')->where('tenant_id', $tenant->id)->count()],
                ['Users', DB::table('users')->where('tenant_id', $tenant->id)->count()],
            ]
        );
        
        $this->command->info('🎉 Default tenant setup complete!');
    }
}
