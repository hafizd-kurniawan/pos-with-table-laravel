<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting complete database seeding...');
        
        // 1. Create default admin user
        $this->command->info('👤 Creating admin user...');
        User::factory()->create([
            'name' => 'Admin POS Restaurant',
            'email' => 'admin@posrestaurant.com',
            'password' => Hash::make('admin123'),
        ]);

        // Additional test users
        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@posrestaurant.com',
            'password' => Hash::make('manager123'),
        ]);

        User::factory()->create([
            'name' => 'Cashier',
            'email' => 'cashier@posrestaurant.com',
            'password' => Hash::make('cashier123'),
        ]);

        // 2. Seed basic data in correct order
        $seeders = [
            'CategorySeeder' => '🏷️  Creating product categories...',
            'ProductSeeder' => '🍽️  Adding restaurant products...',
            'TableCategorySeeder' => '📋 Creating table categories...',
            'TableSeeder' => '🪑 Setting up restaurant tables...',
            'SettingSeeder' => '⚙️  Configuring system settings...',
            'DiscountSeeder' => '💰 Creating discount promotions...',
            'TaxSeeder' => '📊 Setting up taxes & service charges...',
            'OrderSeeder' => '🧾 Creating sample orders (optional)...',
        ];

        foreach ($seeders as $seeder => $message) {
            $this->command->info($message);
            $this->call("Database\\Seeders\\{$seeder}");
        }

        $this->command->info('✅ Database seeding completed successfully!');
        $this->displaySummary();
    }

    private function displaySummary()
    {
        $this->command->info('');
        $this->command->info('📊 Database Summary:');
        $this->command->info('==================');
        
        $summary = [
            'Users' => \App\Models\User::count(),
            'Categories' => \App\Models\Category::count(),
            'Products' => \App\Models\Product::count(),
            'Table Categories' => \App\Models\TableCategory::count(),
            'Tables' => \App\Models\Table::count(),
            'Discounts' => \App\Models\Discount::count(),
            'Taxes' => \App\Models\Tax::count(),
            'Orders' => \App\Models\Order::count(),
            'Order Items' => \App\Models\OrderItem::count(),
        ];

        foreach ($summary as $model => $count) {
            $this->command->info("• {$model}: {$count}");
        }

        $this->command->info('');
        $this->command->info('🔐 Default Login Credentials:');
        $this->command->info('============================');
        $this->command->info('Admin: admin@posrestaurant.com / admin123');
        $this->command->info('Manager: manager@posrestaurant.com / manager123');
        $this->command->info('Cashier: cashier@posrestaurant.com / cashier123');
        $this->command->info('');
    }
}
