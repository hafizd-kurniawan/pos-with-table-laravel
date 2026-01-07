<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompleteSeeder extends Seeder
{
    /**
     * Complete seeding with sample data for development/testing
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting COMPLETE database seeding with sample data...');
        $this->command->info('This includes all basic data + sample orders for testing');
        $this->command->info('');

        // Run basic seeders first
        $this->call(DatabaseSeeder::class);

        $this->command->info('');
        $this->command->info('📦 Adding sample data for development...');

        // Add sample orders and transactions
        $this->command->info('🧾 Creating sample orders...');
        $this->call(OrderSeeder::class);

        $this->command->info('');
        $this->displayCompleteSummary();
    }

    private function displayCompleteSummary()
    {
        $this->command->info('✅ COMPLETE Database seeding finished!');
        $this->command->info('');
        $this->command->info('📊 Complete Database Summary:');
        $this->command->info('=============================');
        
        $summary = [
            'Users' => \App\Models\User::count(),
            'Categories' => \App\Models\Category::count(),
            'Products' => \App\Models\Product::count(),
            'Table Categories' => \App\Models\TableCategory::count(),
            'Tables' => \App\Models\Table::count(),
            'Orders' => \App\Models\Order::count(),
            'Order Items' => \App\Models\OrderItem::count(),
        ];

        foreach ($summary as $model => $count) {
            $this->command->info("• {$model}: {$count}");
        }

        $this->command->info('');
        $this->command->info('🎯 Development Ready!');
        $this->command->info('Your POS Restaurant system is now ready for development/testing');
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('Admin: admin@posrestaurant.com / admin123');
        $this->command->info('Manager: manager@posrestaurant.com / manager123');
        $this->command->info('Cashier: cashier@posrestaurant.com / cashier123');
        $this->command->info('');
        
        $this->command->info('🛜 API Endpoints Ready:');
        $this->command->info('• GET /api/products - List all products');
        $this->command->info('• GET /api/tables - List all tables');
        $this->command->info('• GET /api/orders - List all orders');
        $this->command->info('• POST /api/products/check-stock - Check stock availability');
        $this->command->info('');
    }
}
