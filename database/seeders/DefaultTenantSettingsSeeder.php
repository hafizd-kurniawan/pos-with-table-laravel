<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Tenant;

class DefaultTenantSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default settings for a specific tenant or all tenants.
     */
    public function run(): void
    {
        $tenantId = $this->command->option('tenant');

        if ($tenantId) {
            $this->createDefaultSettings($tenantId);
            $this->command->info("✅ Default settings created for tenant ID: {$tenantId}");
        } else {
            // Create for all tenants
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $this->createDefaultSettings($tenant->id);
            }
            $this->command->info("✅ Default settings created for {$tenants->count()} tenants");
        }
    }

    /**
     * Get default settings configuration
     */
    public static function getDefaultSettings(): array
    {
        return [
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'Self Order POS',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama aplikasi yang akan ditampilkan di sistem dan struk'
            ],
            [
                'key' => 'restaurant_address',
                'value' => 'Jl. Raya No. 123, Jakarta',
                'type' => 'textarea',
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
                'key' => 'restaurant_email',
                'value' => 'info@restaurant.com',
                'type' => 'email',
                'group' => 'general',
                'label' => 'Email Restoran',
                'description' => 'Email restoran untuk keperluan kontak'
            ],
            [
                'key' => 'restaurant_website',
                'value' => 'https://www.restaurant.com',
                'type' => 'url',
                'group' => 'general',
                'label' => 'Website Restoran',
                'description' => 'Website restoran yang akan ditampilkan di struk'
            ],

            // Appearance Settings
            [
                'key' => 'logo_url',
                'value' => '',
                'type' => 'file',
                'group' => 'appearance',
                'label' => 'Logo Restoran',
                'description' => 'Upload logo restoran (format: PNG, JPG, SVG). Akan ditampilkan di struk dan self-order'
            ],
            [
                'key' => 'primary_color',
                'value' => '#000000',
                'type' => 'color',
                'group' => 'appearance',
                'label' => 'Warna Utama',
                'description' => 'Warna utama yang digunakan di aplikasi self-order'
            ],
            [
                'key' => 'banner_image',
                'value' => '',
                'type' => 'file',
                'group' => 'appearance',
                'label' => 'Banner Self-Order',
                'description' => 'Banner yang ditampilkan di halaman self-order'
            ],

            // Payment Gateway Settings
            [
                'key' => 'midtrans_server_key',
                'value' => '',
                'type' => 'text',
                'group' => 'payment',
                'label' => 'Midtrans Server Key',
                'description' => 'Server Key dari dashboard.sandbox.midtrans.com atau dashboard.midtrans.com'
            ],
            [
                'key' => 'midtrans_client_key',
                'value' => '',
                'type' => 'text',
                'group' => 'payment',
                'label' => 'Midtrans Client Key',
                'description' => 'Client Key dari dashboard Midtrans'
            ],
            [
                'key' => 'midtrans_is_production',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'payment',
                'label' => 'Midtrans Production Mode',
                'description' => 'Aktifkan untuk production (pembayaran riil). Nonaktifkan untuk sandbox (testing)'
            ],

            // Order Settings
            [
                'key' => 'receipt_footer_text',
                'value' => 'Terima kasih atas kunjungan Anda!',
                'type' => 'textarea',
                'group' => 'order',
                'label' => 'Teks Footer Struk',
                'description' => 'Teks yang ditampilkan di bagian bawah struk'
            ],
            [
                'key' => 'allow_self_order',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'order',
                'label' => 'Aktifkan Self-Order',
                'description' => 'Izinkan customer untuk memesan sendiri melalui aplikasi'
            ],
            [
                'key' => 'require_customer_info',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'order',
                'label' => 'Wajib Isi Data Customer',
                'description' => 'Customer harus mengisi nama/email/telepon saat checkout'
            ],

            // Notification Settings
            [
                'key' => 'n8n_webhook_url',
                'value' => '',
                'type' => 'url',
                'group' => 'notification',
                'label' => 'N8N Webhook URL',
                'description' => 'URL webhook N8N untuk notifikasi order baru'
            ],
            [
                'key' => 'firebase_fcm_token',
                'value' => '',
                'type' => 'textarea',
                'group' => 'notification',
                'label' => 'Firebase FCM Token',
                'description' => 'Token FCM untuk push notification ke mobile app'
            ],
        ];
    }

    /**
     * Create default settings for a tenant
     */
    private function createDefaultSettings(int $tenantId): void
    {
        $settings = self::getDefaultSettings();

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'key' => $setting['key']
                ],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'label' => $setting['label'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
