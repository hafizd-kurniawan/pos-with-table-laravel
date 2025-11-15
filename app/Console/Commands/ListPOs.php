<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;

class ListPOs extends Command
{
    protected $signature = 'list:pos {--tenant=}';
    protected $description = 'List purchase orders';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        
        $query = PurchaseOrder::with('items');
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        $pos = $query->latest()->take(10)->get();
        
        if ($pos->isEmpty()) {
            $this->warn('No POs found');
            return;
        }
        
        $this->table(
            ['ID', 'PO Number', 'Tenant', 'Status', 'Items Count', 'Total', 'Created'],
            $pos->map(fn($po) => [
                $po->id,
                $po->po_number,
                $po->tenant_id,
                $po->status,
                $po->items->count(),
                number_format($po->total_amount, 0, ',', '.'),
                $po->created_at->format('Y-m-d H:i'),
            ])
        );
        
        $this->line("\nTo test receive: php artisan test:po-receive {po_id}");
    }
}
