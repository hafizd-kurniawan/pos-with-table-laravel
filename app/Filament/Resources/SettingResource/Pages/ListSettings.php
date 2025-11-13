<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;
    
    protected static ?string $title = 'Settings';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('setup_all_settings')
                ->label('Setup All Default Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->visible(fn () => $this->needsDefaultSetup())
                ->action(function () {
                    try {
                        $created = $this->ensureAllDefaultSettingsExist();
                        
                        Notification::make()
                            ->title('Default settings created')
                            ->body("Created {$created} settings. You can now customize them below.")
                            ->success()
                            ->send();
                            
                        // Refresh page
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('setup_appearance')
                ->label('Setup Appearance')
                ->icon('heroicon-o-paint-brush')
                ->color('warning')
                ->visible(fn () => $this->needsAppearanceSetup())
                ->action(function () {
                    try {
                        $this->ensureAppearanceSettingsExist();
                        
                        Notification::make()
                            ->title('Appearance settings created')
                            ->body('You can now upload logo, set colors, and customize appearance. Look for "Appearance" group.')
                            ->success()
                            ->send();
                            
                        // Refresh page
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('Settings might already exist. Please check the Appearance group below.')
                            ->warning()
                            ->send();
                    }
                }),
            
            Actions\Action::make('setup_midtrans')
                ->label('Setup Midtrans Payment')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->visible(fn () => $this->needsMidtransSetup())
                ->action(function () {
                    try {
                        $this->ensureMidtransSettingsExist();
                        
                        Notification::make()
                            ->title('Midtrans settings created')
                            ->body('You can now configure your Midtrans credentials below. Look for "Payment" group.')
                            ->success()
                            ->send();
                            
                        // Refresh page
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('Settings might already exist. Please check the Payment group below.')
                            ->warning()
                            ->send();
                    }
                }),
            
            Actions\CreateAction::make(),
        ];
    }
    
    protected function needsMidtransSetup(): bool
    {
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user || !$user->tenant_id) {
            return false;
        }
        
        // Check if ALL 3 Midtrans settings exist
        $count = \App\Models\Setting::where('tenant_id', $user->tenant_id)
            ->whereIn('key', ['midtrans_server_key', 'midtrans_client_key', 'midtrans_is_production'])
            ->count();
            
        // Show button only if NOT all 3 exist
        return $count < 3;
    }
    
    protected function ensureMidtransSettingsExist(): void
    {
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user || !$user->tenant_id) {
            return;
        }
        
        // Create server key setting
        \App\Models\Setting::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'key' => 'midtrans_server_key'],
            [
                'value' => '',
                'type' => 'text',
                'group' => 'payment',
                'label' => 'Midtrans Server Key',
                'description' => 'Your Midtrans Server Key from dashboard.sandbox.midtrans.com or dashboard.midtrans.com',
            ]
        );
        
        // Create client key setting
        \App\Models\Setting::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'key' => 'midtrans_client_key'],
            [
                'value' => '',
                'type' => 'text',
                'group' => 'payment',
                'label' => 'Midtrans Client Key',
                'description' => 'Your Midtrans Client Key from dashboard.sandbox.midtrans.com or dashboard.midtrans.com',
            ]
        );
        
        // Create is_production setting
        \App\Models\Setting::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'key' => 'midtrans_is_production'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'payment',
                'label' => 'Midtrans Production Mode',
                'description' => 'Enable this for production (real payments). Disable for sandbox (testing).',
            ]
        );
    }
    
    /**
     * Check if needs default setup (all settings)
     */
    protected function needsDefaultSetup(): bool
    {
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user || !$user->tenant_id) {
            return false;
        }
        
        // Show button if less than 10 settings exist
        $count = \App\Models\Setting::where('tenant_id', $user->tenant_id)->count();
        return $count < 10;
    }
    
    /**
     * Check if needs appearance setup
     */
    protected function needsAppearanceSetup(): bool
    {
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user || !$user->tenant_id) {
            return false;
        }
        
        // Check if appearance settings exist
        $count = \App\Models\Setting::where('tenant_id', $user->tenant_id)
            ->where('group', 'appearance')
            ->count();
            
        // Show button only if NOT all 3 appearance settings exist
        return $count < 3;
    }
    
    /**
     * Ensure all default settings exist
     */
    protected function ensureAllDefaultSettingsExist(): int
    {
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user || !$user->tenant_id) {
            throw new \Exception('User tenant not found');
        }
        
        $settings = \Database\Seeders\DefaultTenantSettingsSeeder::getDefaultSettings();
        $created = 0;
        
        foreach ($settings as $setting) {
            $exists = \App\Models\Setting::where('tenant_id', $user->tenant_id)
                ->where('key', $setting['key'])
                ->exists();
                
            if (!$exists) {
                $newSetting = new \App\Models\Setting();
                $newSetting->tenant_id = $user->tenant_id;
                $newSetting->key = $setting['key'];
                $newSetting->value = $setting['value'];
                $newSetting->type = $setting['type'];
                $newSetting->group = $setting['group'];
                $newSetting->label = $setting['label'];
                $newSetting->description = $setting['description'];
                $newSetting->save();
                $created++;
            }
        }
        
        return $created;
    }
    
    /**
     * Ensure appearance settings exist
     */
    protected function ensureAppearanceSettingsExist(): void
    {
        $userId = auth()->id();
        $user = \DB::table('users')->where('id', $userId)->first();
        
        if (!$user || !$user->tenant_id) {
            return;
        }
        
        // Appearance settings
        $appearanceSettings = [
            [
                'key' => 'logo_url',
                'value' => '',
                'type' => 'file',
                'group' => 'appearance',
                'label' => 'Logo Restoran',
                'description' => 'Upload logo restoran (format: PNG, JPG, SVG). Akan ditampilkan di struk dan self-order',
            ],
            [
                'key' => 'primary_color',
                'value' => '#F59E0B',
                'type' => 'color',
                'group' => 'appearance',
                'label' => 'Warna Utama',
                'description' => 'Warna utama yang digunakan di aplikasi self-order',
            ],
            [
                'key' => 'banner_image',
                'value' => '',
                'type' => 'file',
                'group' => 'appearance',
                'label' => 'Banner Self-Order',
                'description' => 'Banner yang ditampilkan di halaman self-order',
            ],
        ];
        
        foreach ($appearanceSettings as $setting) {
            \App\Models\Setting::firstOrCreate(
                [
                    'tenant_id' => $user->tenant_id,
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