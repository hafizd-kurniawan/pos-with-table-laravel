<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Table;

class RegenerateTableQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tables:regenerate-qr {--tenant_id= : Regenerate only for specific tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate QR codes for all tables with correct tenant URL format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting QR code regeneration...');
        $this->newLine();
        
        // Build query
        $query = Table::with('tenant');
        
        // Filter by tenant if specified
        if ($tenantId = $this->option('tenant_id')) {
            $query->where('tenant_id', $tenantId);
            $this->info("Filtering by tenant_id: {$tenantId}");
        }
        
        $tables = $query->get();
        
        if ($tables->isEmpty()) {
            $this->warn('No tables found!');
            return Command::FAILURE;
        }
        
        $this->info("Found {$tables->count()} tables");
        $this->newLine();
        
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        
        $this->withProgressBar($tables, function ($table) use (&$successCount, &$errorCount, &$skippedCount) {
            $tenant = $table->tenant;
            
            if (!$tenant) {
                $this->newLine();
                $this->warn("⚠️  Table '{$table->name}' (ID: {$table->id}): No tenant found - SKIPPED");
                $skippedCount++;
                return;
            }
            
            try {
                // Generate new URL with tenant format
                $url = url("/order/{$tenant->slug}-{$tenant->short_uuid}/{$table->name}");
                
                // Update QR code
                $table->update(['qr_code' => $url]);
                
                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Table '{$table->name}': {$e->getMessage()}");
                $errorCount++;
            }
        });
        
        $this->newLine(2);
        
        // Summary
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Success', $successCount],
                ['❌ Failed', $errorCount],
                ['⚠️  Skipped (No Tenant)', $skippedCount],
                ['📦 Total', $tables->count()],
            ]
        );
        
        $this->newLine();
        
        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} table(s) failed to regenerate QR code");
            return Command::FAILURE;
        }
        
        if ($skippedCount > 0) {
            $this->warn("⚠️  {$skippedCount} table(s) skipped (no tenant). Please assign tenant_id first.");
        }
        
        $this->info("✅ Successfully regenerated {$successCount} QR codes!");
        
        return Command::SUCCESS;
    }
}
