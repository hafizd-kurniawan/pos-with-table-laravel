<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'name' => 'Biaya Layanan',
                'type' => 'layanan',
                'value' => 5.00,
                'status' => 'active',
                'description' => 'Service charge untuk pelayanan restoran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pajak PB1',
                'type' => 'pajak',
                'value' => 10.00,
                'status' => 'active',
                'description' => 'Pajak Penjualan Barang Mewah (PB1)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'PPN',
                'type' => 'pajak',
                'value' => 11.00,
                'status' => 'active',
                'description' => 'Pajak Pertambahan Nilai',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Service Charge Premium',
                'type' => 'layanan',
                'value' => 8.00,
                'status' => 'inactive',
                'description' => 'Service charge untuk meja VIP dan Private',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pajak Daerah',
                'type' => 'pajak',
                'value' => 2.50,
                'status' => 'active',
                'description' => 'Pajak daerah sesuai peraturan setempat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::create($tax);
        }

        $this->command->info("✅ Created " . count($taxes) . " tax records");
    }
}
