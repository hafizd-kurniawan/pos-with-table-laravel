<?php

namespace App\Models;

use App\Services\QRCodeService;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'category_id',
        'name', 
        'description',
        'location',
        'customer_name',
        'customer_phone',
        'table_name',
        'qr_code', 
        'status', 
        'table_status',
        'service_type',
        'start_time',
        'order_id',
        'payment_amount',
        'x_position',
        'y_position',
        'capacity',
        'party_size',
        'reservation_time',
        'pax_capacity',
        'waiter_assigned',
        'occupied_at',
        'last_activity',
        'special_notes'
    ];

    protected $casts = [
        'x_position' => 'decimal:2',
        'y_position' => 'decimal:2',
        'capacity' => 'integer',
        'party_size' => 'integer',
        'pax_capacity' => 'integer',
        'order_id' => 'integer',
        'payment_amount' => 'decimal:2',
        'reservation_time' => 'datetime',
        'occupied_at' => 'datetime',
        'last_activity' => 'datetime',
    ];

    // Event listeners
    protected static function boot()
    {
        parent::boot();
        
        // Auto set position for new tables
        static::creating(function ($table) {
            if (empty($table->x_position) || empty($table->y_position)) {
                $lastTable = static::orderBy('id', 'desc')->first();
                $totalTables = static::count();
                
                $gridSize = 150; // Jarak antar table (pixel)
                $tablesPerRow = 4; // Jumlah table per baris
                $startX = 50; // Posisi X awal
                $startY = 50; // Posisi Y awal
                
                $row = intval($totalTables / $tablesPerRow);
                $col = $totalTables % $tablesPerRow;
                
                $table->x_position = $startX + ($col * $gridSize);
                $table->y_position = $startY + ($row * $gridSize);
            }
        });
        
        // Auto-generate QR code after table is saved (not in same transaction)
        static::saved(function ($table) {
            // Only generate if qr_code is empty
            if (empty($table->qr_code)) {
                // Use dispatch to avoid blocking
                dispatch(function () use ($table) {
                    $url = url("/order/{$table->name}");
                    // Use updateQuietly to avoid triggering events again
                    $table->updateQuietly(['qr_code' => $url]);
                })->afterResponse();
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(TableCategory::class, 'category_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function currentReservation()
    {
        return $this->hasOne(Reservation::class)
                    ->where('status', 'confirmed')
                    ->whereDate('reservation_date', today());
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    public function scopePendingBill($query)
    {
        return $query->where('status', 'pending_bill');
    }
    
    // Accessors & Mutators
    
    /**
     * Get the QR code URL for this table
     */
    public function getQrUrlAttribute()
    {
        return url("/order/{$this->name}");
    }
    
    /**
     * Generate QR code image data URL
     */
    public function getQrImageDataUrlAttribute()
    {
        $url = $this->qr_code ?: url("/order/{$this->name}");
        return QRCodeService::generateDataUrl($url, 'svg', 200);
    }
    
    /**
     * Get status badge color for Filament
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'available' => 'success',
            'occupied' => 'warning',
            'reserved' => 'info', 
            'maintenance' => 'danger',
            default => 'gray',
        };
    }
    
    /**
     * Get status icon for display
     */
    public function getStatusIconAttribute()
    {
        return match ($this->status) {
            'available' => 'heroicon-m-check-circle',
            'occupied' => 'heroicon-m-user-group',
            'reserved' => 'heroicon-m-clock',
            'maintenance' => 'heroicon-m-wrench-screwdriver',
            default => 'heroicon-m-question-mark-circle',
        };
    }
    
    // Helper methods
    
    /**
     * Manually generate QR code
     */
    public function generateQrCode()
    {
        $url = url("/order/{$this->name}");
        $this->update(['qr_code' => $url]);
        return $url;
    }
    
    /**
     * Check if table is available for new orders
     */
    public function isAvailable()
    {
        return $this->status === 'available';
    }
}
