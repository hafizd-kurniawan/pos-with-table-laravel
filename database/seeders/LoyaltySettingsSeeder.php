<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoyaltySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'loyalty_earning_rate'], [
            'value' => '10000',
            'type' => 'number',
            'group' => 'loyalty',
            'label' => 'Amount to Earn 1 Point',
            'description' => 'Spend this amount in Rupiah to earn 1 loyalty point (e.g., 10000 = Spend 10k get 1 point).',
        ]);

        \App\Models\Setting::updateOrCreate(['key' => 'loyalty_redemption_value'], [
            'value' => '1',
            'type' => 'number',
            'group' => 'loyalty',
            'label' => 'Redemption Value per Point',
            'description' => 'Value of 1 point in Rupiah when redeeming (e.g., 1 = 1 point is Rp 1).',
        ]);

        \App\Models\Setting::updateOrCreate(['key' => 'loyalty_min_redemption'], [
            'value' => '0',
            'type' => 'number',
            'group' => 'loyalty',
            'label' => 'Minimum Points to Redeem',
            'description' => 'Minimum points required to start redeeming.',
        ]);
    }
}
