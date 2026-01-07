<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

/**
 * SubscriptionPlanSeeder
 * 
 * Created: 2025-11-13 04:26:00 WIB
 * Purpose: Seed initial subscription plans (Bronze/Silver/Gold/Platinum)
 */
class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'description' => 'Paket dasar untuk restaurant kecil atau yang baru memulai. Cocok untuk mencoba fitur-fitur POS.',
                'duration_days' => 30, // 1 bulan
                'price' => 150000.00, // Rp 150.000
                'discount_percentage' => 0,
                'max_products' => 100,
                'max_orders_per_day' => 200,
                'max_users' => 3,
                'max_tables' => 20,
                'max_reservations_per_day' => 10,
                'features' => [
                    'POS Basic',
                    'Order Management',
                    'Table Management',
                    'QRIS Payment',
                    'Email Support',
                ],
                'is_active' => true,
                'is_popular' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'description' => 'Paket 3 bulan dengan diskon! Ideal untuk restaurant menengah dengan traffic sedang.',
                'duration_days' => 90, // 3 bulan
                'price' => 400000.00, // Rp 400.000 (hemat 50rb dari 3x150k)
                'discount_percentage' => 11.11, // Hemat 50rb
                'max_products' => 500,
                'max_orders_per_day' => 1000,
                'max_users' => 10,
                'max_tables' => 50,
                'max_reservations_per_day' => 50,
                'features' => [
                    'Semua fitur Bronze',
                    'Reservasi Online',
                    'Multi Discount',
                    'Tax Configuration',
                    'WhatsApp Notification (N8N)',
                    'Email + Chat Support',
                ],
                'is_active' => true,
                'is_popular' => true, // Most popular!
                'display_order' => 2,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'description' => 'Paket 6 bulan untuk restaurant sibuk. Unlimited produk & order, hemat lebih banyak!',
                'duration_days' => 180, // 6 bulan
                'price' => 750000.00, // Rp 750.000 (hemat 150rb dari 6x150k = 900k)
                'discount_percentage' => 16.67, // Hemat 150rb
                'max_products' => -1, // Unlimited
                'max_orders_per_day' => -1, // Unlimited
                'max_users' => 20,
                'max_tables' => -1, // Unlimited
                'max_reservations_per_day' => -1, // Unlimited
                'features' => [
                    'Semua fitur Silver',
                    'Produk Unlimited',
                    'Order Unlimited',
                    'Meja Unlimited',
                    'Advanced Analytics',
                    'Stock Management',
                    'Priority Support',
                ],
                'is_active' => true,
                'is_popular' => false,
                'display_order' => 3,
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'description' => 'Paket 1 tahun untuk chain restaurant. Hemat maksimal dengan fitur paling lengkap!',
                'duration_days' => 365, // 1 tahun
                'price' => 1400000.00, // Rp 1.400.000 (hemat 400rb dari 12x150k = 1.800k)
                'discount_percentage' => 22.22, // Hemat 400rb
                'max_products' => -1, // Unlimited
                'max_orders_per_day' => -1, // Unlimited
                'max_users' => -1, // Unlimited
                'max_tables' => -1, // Unlimited
                'max_reservations_per_day' => -1, // Unlimited
                'features' => [
                    'Semua fitur Gold',
                    'Staff Unlimited',
                    'Multi Location Support',
                    'Custom Integration',
                    'Dedicated Account Manager',
                    'Priority Support 24/7',
                    'Custom Feature Request',
                ],
                'is_active' => true,
                'is_popular' => false,
                'display_order' => 4,
            ],
        ];
        
        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
        
        $this->command->info('✅ Subscription plans seeded successfully!');
        $this->command->table(
            ['Plan', 'Price', 'Duration', 'Discount'],
            [
                ['Bronze', 'Rp 150.000', '1 Bulan', '-'],
                ['Silver', 'Rp 400.000', '3 Bulan', '11% (Hemat 50rb)'],
                ['Gold', 'Rp 750.000', '6 Bulan', '17% (Hemat 150rb)'],
                ['Platinum', 'Rp 1.400.000', '1 Tahun', '22% (Hemat 400rb)'],
            ]
        );
    }
}
