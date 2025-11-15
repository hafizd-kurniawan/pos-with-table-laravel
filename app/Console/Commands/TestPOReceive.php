<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;

class TestPOReceive extends Command
{
    protected $signature = 'test:po-receive {po_id}';
    protected $description = 'Test PO receive and check stock update';

    public function handle()
    {
        $poId = $this->argument('po_id');
        $po = PurchaseOrder::with('items.ingredient')->find($poId);
        
        if (!$po) {
            $this->error("PO not found!");
            return 1;
        }
        
        $this->info("PO: {$po->po_number}");
        $this->info("Status: {$po->status}");
        $this->info("Items count: " . $po->items->count());
        
        if ($po->items->count() === 0) {
            $this->error("❌ NO ITEMS! This is the problem!");
            return 1;
        }
        
        $this->line("\nItems:");
        foreach ($po->items as $item) {
            $this->line("- {$item->ingredient->name}: {$item->quantity} {$item->ingredient->unit}");
            $this->line("  Current stock: {$item->ingredient->current_stock}");
        }
        
        if ($po->status === 'sent') {
            $this->line("\nReceiving PO...");
            
            try {
                $po->receive(auth()->id() ?? 1);
                $this->info("✅ PO Received!");
                
                $this->line("\nStock after receive:");
                $po->refresh();
                foreach ($po->items as $item) {
                    $item->ingredient->refresh();
                    $this->line("- {$item->ingredient->name}: {$item->ingredient->current_stock} {$item->ingredient->unit}");
                }
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        } else {
            $this->warn("PO status is '{$po->status}', must be 'sent' to receive");
        }
        
        return 0;
    }
}
