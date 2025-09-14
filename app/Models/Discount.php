<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'status',
        'expired_date'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expired_date' => 'date',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expired_date')
                          ->orWhere('expired_date', '>=', now());
                    });
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive')
                    ->orWhere('expired_date', '<', now());
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active' && 
               ($this->expired_date === null || $this->expired_date >= now());
    }

    public function calculateDiscount($amount)
    {
        if (!$this->isActive()) {
            return 0;
        }

        if ($this->type === 'percentage') {
            return ($amount * $this->value) / 100;
        }

        return min($this->value, $amount);
    }
}
