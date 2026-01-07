<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Setting;
use Database\Seeders\DefaultTenantSettingsSeeder;

class SeedTenantSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:seed-settings 
                            {--tenant= : Specific tenant ID to seed}
                            {--all : Seed all tenants}
                            {--force : Force recreate all settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default settings for tenants (app name, logo, contact info, payment config, etc)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $all = $this->option('all');
        $force = $this->option('force');
        
        if (!$tenantId && !$all) {
            $this->error('❌ Please specify --tenant=ID or --all');
            return Command::FAILURE;
        }
        
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("❌ Tenant ID {$tenantId} not found!");
                return Command::FAILURE;
            }
            
            $this->seedTenant($tenant, $force);
        } else {
            $tenants = Tenant::all();
            $this->info("🚀 Seeding settings for {$tenants->count()} tenants...\n");
            
            foreach ($tenants as $tenant) {
                $this->seedTenant($tenant, $force);
            }
            
            $this->info("\n✅ All tenants seeded successfully!");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Seed settings for a specific tenant
     */
    private function seedTenant(Tenant $tenant, bool $force = false)
    {
        $this->info("📝 Tenant #{$tenant->id}: {$tenant->name}");
        
        $settings = DefaultTenantSettingsSeeder::getDefaultSettings();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        
        foreach ($settings as $setting) {
            $existing = Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', $setting['key'])
                ->first();
            
            if ($existing) {
                if ($force) {
                    // Force update
                    $existing->update([
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'group' => $setting['group'],
                        'label' => $setting['label'],
                        'description' => $setting['description'],
                    ]);
                    $updated++;
                    $this->line("   🔄 Updated: {$setting['key']}");
                } else {
                    // Only update metadata, keep value
                    $existing->update([
                        'type' => $setting['type'],
                        'group' => $setting['group'],
                        'label' => $setting['label'],
                        'description' => $setting['description'],
                    ]);
                    $skipped++;
                    $this->line("   ⏭️  Skipped: {$setting['key']} (exists)");
                }
            } else {
                // Create new WITHOUT triggering trait boot
                $newSetting = new Setting();
                $newSetting->tenant_id = $tenant->id;
                $newSetting->key = $setting['key'];
                $newSetting->value = $setting['value'];
                $newSetting->type = $setting['type'];
                $newSetting->group = $setting['group'];
                $newSetting->label = $setting['label'];
                $newSetting->description = $setting['description'];
                $newSetting->save();
                
                $created++;
                $this->line("   ✅ Created: {$setting['key']}");
            }
        }
        
        $this->info("   📊 Summary: Created={$created}, Updated={$updated}, Skipped={$skipped}\n");
    }
}
