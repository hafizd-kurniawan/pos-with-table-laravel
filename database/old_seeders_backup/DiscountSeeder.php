<?php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            [
                'name' => 'Happy Hour Discount',
                'description' => 'Special discount for orders during happy hour (14:00-16:00)',
                'type' => 'percentage',
                'value' => 15.00,
                'status' => 'active',
                'expired_date' => Carbon::now()->addMonths(6)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Student Discount',
                'description' => 'Special discount for students with valid ID',
                'type' => 'percentage',
                'value' => 10.00,
                'status' => 'active',
                'expired_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Senior Citizen Discount',
                'description' => 'Discount for senior citizens (60+ years old)',
                'type' => 'percentage',
                'value' => 20.00,
                'status' => 'active',
                'expired_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'First Time Customer',
                'description' => 'Welcome discount for new customers',
                'type' => 'fixed',
                'value' => 5000.00,
                'status' => 'active',
                'expired_date' => Carbon::now()->addYear()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Weekend Special',
                'description' => 'Special weekend discount for families',
                'type' => 'percentage',
                'value' => 12.00,
                'status' => 'active',
                'expired_date' => Carbon::now()->addMonths(3)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Expired Promotion',
                'description' => 'This is an expired promotion for testing',
                'type' => 'percentage',
                'value' => 25.00,
                'status' => 'inactive',
                'expired_date' => Carbon::now()->subDays(10)->toDateString(),
                'created_at' => Carbon::now()->subMonth(),
                'updated_at' => Carbon::now()->subMonth(),
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::create($discount);
        }

        $this->command->info("✅ Created " . count($discounts) . " discount records");
    }
}
