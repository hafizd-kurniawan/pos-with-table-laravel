<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class CleanupTestPOs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:test-pos {--tenant= : Tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup test/orphaned purchase orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        
        if (!$tenantId) {
            $this->error('Please specify tenant ID: --tenant=X');
            return 1;
        }
        
        $this->info("Cleaning up POs for tenant {$tenantId}...");
        
        DB::transaction(function () use ($tenantId) {
            // Delete PO items first (foreign key)
            $itemsDeleted = DB::table('purchase_order_items')
                ->whereIn('purchase_order_id', function($query) use ($tenantId) {
                    $query->select('id')
                        ->from('purchase_orders')
                        ->where('tenant_id', $tenantId);
                })
                ->delete();
            
            $this->line("Deleted {$itemsDeleted} PO items");
            
            // Delete POs
            $posDeleted = PurchaseOrder::where('tenant_id', $tenantId)
                ->forceDelete(); // Force delete even soft deleted
            
            $this->line("Deleted {$posDeleted} POs");
        });
        
        $this->info('✅ Cleanup complete!');
        $this->info('You can now create PO-202511-0001 again');
        
        return 0;
    }
}
