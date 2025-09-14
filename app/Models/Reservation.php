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
        'reservation_time' => 'datetime:H:i',
        'party_size' => 'integer',
    ];

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

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function getReservationDateTimeAttribute()
    {
        return Carbon::parse($this->reservation_date->format('Y-m-d') . ' ' . $this->reservation_time);
    }

    public function canBeCancelled()
    {
        return $this->isPending() && $this->getReservationDateTimeAttribute()->isFuture();
    }
}
