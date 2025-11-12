<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'party_size',
        'reservation_date',
        'reservation_time',
        'status',
        'notes',
        'special_requests'
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'party_size' => 'integer',
    ];
    
    /**
     * Get reservation_time as time string (H:i:s format)
     * Don't cast to datetime to avoid confusion with date
     */

    // Relationships
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('reservation_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', today());
    }

    // Methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isCheckedIn()
    {
        return $this->status === 'checked_in';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isNoShow()
    {
        return $this->status === 'no_show';
    }

    public function isActive()
    {
        return in_array($this->status, ['confirmed', 'checked_in']);
    }

    /**
     * Get combined reservation datetime
     */
    public function getReservationDateTimeAttribute()
    {
        $date = $this->reservation_date->format('Y-m-d');
        $time = $this->reservation_time; // Already in H:i:s format from DB
        
        return Carbon::parse("{$date} {$time}");
    }
    
    /**
     * Get formatted time for display
     */
    public function getFormattedTimeAttribute()
    {
        return Carbon::parse($this->reservation_time)->format('H:i');
    }

    public function canBeCancelled()
    {
        return $this->isPending() && $this->getReservationDateTimeAttribute()->isFuture();
    }
}
