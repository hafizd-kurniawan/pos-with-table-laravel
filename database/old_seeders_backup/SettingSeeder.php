<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'Self Order POS',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama aplikasi yang akan ditampilkan di sistem'
            ],

            [
                'key' => 'logo_url',
                'value' => '',
                'type' => 'file',
                'group' => 'appearance',
                'label' => 'Logo Restoran',
                'description' => 'Upload logo restoran (format: PNG, JPG, SVG)'
            ],

            [
                'key' => 'tax_percentage',
                'value' => '11',
                'type' => 'number',
                'group' => 'general',
                'label' => 'Tax Percentage',
                'description' => 'Persentase PPN yang akan dikenakan pada setiap transaksi'
            ],

            [
                'key' => 'restaurant_name',
                'value' => 'resto',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nama Restoran',
                'description' => 'Nama lengkap restoran yang akan ditampilkan di struk dan aplikasi'
            ],

            [
                'key' => 'restaurant_address',
                'value' => 'Jl. Raya 123',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Alamat Restoran',
                'description' => 'Alamat lengkap restoran yang akan ditampilkan di struk'
            ],

            [
                'key' => 'restaurant_phone',
                'value' => '(021) 8765-4321',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nomor Telepon',
                'description' => 'Nomor telepon restoran yang akan ditampilkan di struk'
            ],

            [
                'key' => 'restaurant_website',
                'value' => 'website',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Website Restoran',
                'description' => 'Website restoran yang akan ditampilkan di struk'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully!');
    }
}
