<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailySummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-daily {--date= : Date to generate (Y-m-d format, default: yesterday)} {--tenant= : Specific tenant ID} {--force : Force regenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily sales summaries for all tenants (automatic daily closing)';

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Daily Summary Generation');
        $this->newLine();
        
        // Get date (default yesterday)
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();
        
        $this->info("📅 Date: {$date->format('Y-m-d')}");
        $this->newLine();
        
        $force = $this->option('force');
        
        // Get tenants
        $tenantQuery = Tenant::query();
        if ($this->option('tenant')) {
            $tenantQuery->where('id', $this->option('tenant'));
        }
        $tenants = $tenantQuery->get();
        
        if ($tenants->isEmpty()) {
            $this->warn('⚠️  No tenants found');
            return 0;
        }
        
        $this->info("🏢 Processing {$tenants->count()} tenant(s)...");
        $this->newLine();
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($tenants as $tenant) {
            $tenantName = $tenant->name ?? "Tenant #{$tenant->id}";
            
            try {
                $this->line("  → Processing: {$tenantName}");
                
                // Check if tenant has orders for this date
                $ordersCount = DB::table('orders')
                    ->where('tenant_id', $tenant->id)
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->whereIn('status', ['paid', 'complete'])
                    ->count();
                
                if ($ordersCount == 0) {
                    $this->warn("    ⚠️  No orders found - skipped");
                    continue;
                }
                
                // Generate summary
                $summary = $this->reportService->generateDailySummary(
                    $tenant->id,
                    $date->format('Y-m-d'),
                    $force
                );
                
                $this->info("    ✅ Success! Orders: {$summary->total_orders}, Net Sales: Rp " . number_format($summary->net_sales, 0, ',', '.'));
                
                // Log the generation
                Log::info('Daily summary generated', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenantName,
                    'date' => $date->format('Y-m-d'),
                    'total_orders' => $summary->total_orders,
                    'net_sales' => $summary->net_sales,
                ]);
                
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("    ❌ Error: {$e->getMessage()}");
                
                Log::error('Daily summary generation failed', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenantName,
                    'date' => $date->format('Y-m-d'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $errorCount++;
            }
        }
        
        $this->newLine();
        $this->info("✨ Generation Complete!");
        $this->info("   Success: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("   Errors: {$errorCount}");
        }
        
        return $errorCount > 0 ? 1 : 0;
    }
}
