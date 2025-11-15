<?php

namespace App\Observers;

use App\Models\PurchaseOrder;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "updating" event.
     * Prevent manual status change to 'received' without proper receive process
     */
    public function updating(PurchaseOrder $purchaseOrder): void
    {
        // If status is being changed to 'received'
        if ($purchaseOrder->isDirty('status') && $purchaseOrder->status === 'received') {
            $oldStatus = $purchaseOrder->getOriginal('status');
            
            // Check if received_by is also being set (means proper receive() was called)
            if (!$purchaseOrder->isDirty('received_by')) {
                // Manual status change detected!
                \Log::warning("Prevented manual status change to received", [
                    'po' => $purchaseOrder->po_number,
                    'old_status' => $oldStatus,
                ]);
                
                // Revert status
                $purchaseOrder->status = $oldStatus;
                
                throw new \Exception(
                    '❌ TIDAK BISA! Untuk receive PO, gunakan tombol "Receive" di list PO, ' .
                    'BUKAN dengan mengubah status manual di form edit!'
                );
            }
        }
    }
}
