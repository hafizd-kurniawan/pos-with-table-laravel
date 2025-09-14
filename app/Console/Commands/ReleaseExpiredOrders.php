<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Traits\ManagesStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredOrders extends Command
{
    use ManagesStock;

    protected $signature = 'orders:release-expired';
    protected $description = 'Release stock for expired unpaid orders';

    public function handle()
    {
        // Cari orders yang pending lebih dari 2 menit
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(2))
            ->with('orderItems.product')
            ->get();

        $releasedCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                // Release stock kembali
                $this->releaseStock($order);
                
                // Update status order
                $order->status = 'expired';
                $order->expired_at = now();
                $order->save();

                $releasedCount++;
                
                Log::info('Order expired and stock released', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'table' => $order->table->name ?? 'Unknown'
                ]);

                $this->info("Released stock for order: {$order->code}");
                
            } catch (\Exception $e) {
                Log::error('Failed to release expired order stock', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                
                $this->error("Failed to release stock for order {$order->code}: {$e->getMessage()}");
            }
        }

        $this->info("Released {$releasedCount} expired orders");
        
        return 0;
    }
}
