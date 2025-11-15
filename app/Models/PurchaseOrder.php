<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'subtotal',
        'tax',
        'discount',
        'shipping_cost',
        'total_amount',
        'notes',
        'created_by',
        'received_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'integer',
        'tax' => 'integer',
        'discount' => 'integer',
        'shipping_cost' => 'integer',
        'total_amount' => 'integer',
        'status' => 'string',
    ];

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    /**
     * Auto-generate PO number with timestamp + microtime for uniqueness
     */
    public static function generatePONumber($tenantId): string
    {
        $prefix = 'PO-' . now()->format('Ym') . '-';
        $maxAttempts = 20;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // Get count of POs for this month
            $count = self::where('tenant_id', $tenantId)
                ->where('po_number', 'like', $prefix . '%')
                ->count();
            
            // Generate number: count + 1 + microseconds for uniqueness
            $number = $count + $attempt;
            $poNumber = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            
            // Check if unique
            if (!self::where('po_number', $poNumber)->exists()) {
                return $poNumber;
            }
            
            // If not unique, add small delay and try again
            usleep(10000); // 10ms delay
        }
        
        // Fallback: use timestamp
        return $prefix . now()->format('His');
    }

    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum(DB::raw('quantity * unit_price'));
        $total = $subtotal + $this->tax + $this->shipping_cost - $this->discount;

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $total,
        ]);
    }

    /**
     * Business Methods
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeSent(): bool
    {
        return $this->isDraft() && $this->items()->count() > 0;
    }

    public function canBeReceived(): bool
    {
        return $this->isSent();
    }

    public function canBeCancelled(): bool
    {
        return !$this->isReceived() && !$this->isCancelled();
    }

    /**
     * Mark as sent to supplier
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Receive PO and update stock
     * This should be called through InventoryService for proper stock tracking
     */
    public function receive($userId): void
    {
        if (!$this->canBeReceived()) {
            throw new \Exception('Purchase order cannot be received in current status');
        }

        \Log::info("Receiving PO {$this->po_number}", ['items_count' => $this->items->count()]);

        DB::transaction(function() use ($userId) {
            if ($this->items->count() === 0) {
                \Log::error("PO {$this->po_number} has no items!");
                throw new \Exception('Purchase order has no items');
            }

            foreach ($this->items as $item) {
                \Log::info("Processing item", [
                    'ingredient' => $item->ingredient->name,
                    'quantity' => $item->quantity,
                ]);

                $ingredient = $item->ingredient;
                $oldStock = $ingredient->current_stock;
                $newStock = $oldStock + $item->quantity;

                // Update ingredient stock
                $ingredient->update(['current_stock' => $newStock]);
                \Log::info("Updated stock", ['old' => $oldStock, 'new' => $newStock]);

                // Create stock movement record
                $movement = \App\Models\StockMovement::create([
                    'tenant_id' => $this->tenant_id,
                    'ingredient_id' => $item->ingredient_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $newStock,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $this->id,
                    'user_id' => $userId,
                    'notes' => "PO received: {$this->po_number}",
                    'moved_at' => now(),
                ]);
                \Log::info("Created stock movement", ['id' => $movement->id]);
            }

            // Update PO status
            $this->update([
                'status' => self::STATUS_RECEIVED,
                'actual_delivery_date' => now(),
                'received_by' => $userId,
            ]);
            \Log::info("PO status updated to received");
        });
    }
}
