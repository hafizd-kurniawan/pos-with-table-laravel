<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SaaS Database Seeder
 * 
 * This seeder is SAAS-READY and creates:
 * 1. Super Admin (no tenant)
 * 2. Subscription Plans
 * 3. Default Tenant with sample data
 * 
 * Safe to run in production!
 */
class SaaSDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting SaaS Database Seeding...');
        $this->command->info('');
        
        // ================================================
        // STEP 1: CREATE SUPER ADMIN
        // ================================================
        $this->command->info('👑 Creating Super Admin...');
        
        $superAdmin = User::withoutGlobalScope('tenant')->firstOrCreate(
            ['email' => 'admin@possaas.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'tenant_id' => null, // Super admin has NO tenant
            ]
        );
        
        $this->command->info("   ✅ Super Admin: {$superAdmin->email}");
        
        // ================================================
        // STEP 2: CREATE SUBSCRIPTION PLANS
        // ================================================
        $this->command->info('');
        $this->command->info('💳 Creating Subscription Plans...');
        
        $this->call(SubscriptionPlanSeeder::class);
        
        // ================================================
        // STEP 3: CREATE DEFAULT TENANT (Optional)
        // ================================================
        $this->command->info('');
        $this->command->info('🏢 Creating Default Tenant...');
        
        $this->call(DefaultTenantSeeder::class);
        
        // ================================================
        // SUMMARY
        // ================================================
        $this->displaySummary();
    }
    
    private function displaySummary()
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('📊 SAAS DATABASE SEEDING COMPLETE!');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
        
        // Count data
        $tenants = Tenant::count();
        $plans = SubscriptionPlan::count();
        $superAdmins = User::withoutGlobalScope('tenant')->whereNull('tenant_id')->count();
        
        $this->command->info('📈 Statistics:');
        $this->command->info("   • Subscription Plans: {$plans}");
        $this->command->info("   • Tenants: {$tenants}");
        $this->command->info("   • Super Admins: {$superAdmins}");
        
        $this->command->info('');
        $this->command->info('🔐 Super Admin Login:');
        $this->command->info('   URL: /superadmin/login');
        $this->command->info('   Email: admin@possaas.com');
        $this->command->info('   Password: admin123');
        
        if ($tenants > 0) {
            $this->command->info('');
            $this->command->info('🏢 Default Tenant Login:');
            $this->command->info('   URL: /admin/login');
            $this->command->info('   Email: admin@posrestaurant.com');
            $this->command->info('   Password: password');
        }
        
        $this->command->info('');
        $this->command->info('✅ Ready for production use!');
        $this->command->info('');
    }
}
