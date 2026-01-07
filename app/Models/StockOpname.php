<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class StockOpname extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'tenant_id',
        'opname_number',
        'opname_date',
        'status',
        'user_id',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'completed_at' => 'datetime',
        'status' => 'string',
    ];

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Auto-generate opname number
     */
    public static function generateOpnameNumber($tenantId): string
    {
        $lastOpname = self::where('tenant_id', $tenantId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->latest('id')
            ->first();

        $number = $lastOpname ? ((int) substr($lastOpname->opname_number, -4)) + 1 : 1;
        return 'SO-' . now()->format('Ym') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Business Methods
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeCompleted(): bool
    {
        return $this->isDraft() && $this->items()->count() > 0;
    }

    /**
     * Complete stock opname and adjust stock
     */
    public function complete(): void
    {
        if (!$this->canBeCompleted()) {
            throw new \Exception('Stock opname cannot be completed');
        }

        DB::transaction(function() {
            foreach ($this->items as $item) {
                if ($item->difference != 0) {
                    $ingredient = $item->ingredient;
                    $oldStock = $ingredient->current_stock;
                    $newStock = $item->physical_qty;

                    // Update ingredient stock to match physical count
                    $ingredient->updateStock($newStock);

                    // Create stock movement record
                    StockMovement::create([
                        'tenant_id' => $this->tenant_id,
                        'ingredient_id' => $item->ingredient_id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'quantity' => abs($item->difference),
                        'stock_before' => $oldStock,
                        'stock_after' => $newStock,
                        'reference_type' => 'stock_opname',
                        'reference_id' => $this->id,
                        'user_id' => $this->user_id,
                        'notes' => "Stock opname adjustment: {$item->difference} {$ingredient->unit}. " . ($item->notes ?? ''),
                        'moved_at' => now(),
                    ]);

                    // Check if low stock after adjustment
                    if ($ingredient->isLowStock()) {
                        event(new \App\Events\LowStockDetected($ingredient));
                    }
                }
            }

            // Mark as completed
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Get total differences summary
     */
    public function getDifferencesSummaryAttribute(): array
    {
        return [
            'total_items' => $this->items()->count(),
            'items_with_difference' => $this->items()->where('difference', '!=', 0)->count(),
            'total_overage_value' => $this->items()
                ->where('difference', '>', 0)
                ->get()
                ->sum(function ($item) {
                    return $item->difference * $item->ingredient->cost_per_unit;
                }),
            'total_shortage_value' => $this->items()
                ->where('difference', '<', 0)
                ->get()
                ->sum(function ($item) {
                    return abs($item->difference) * $item->ingredient->cost_per_unit;
                }),
        ];
    }
}
