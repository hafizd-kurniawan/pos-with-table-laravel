<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Tax extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'type',
        'value',
        'status',
        'description'
    ];

    protected $casts = [
        'value' => 'float',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLayanan($query)
    {
        return $query->where('type', 'layanan');
    }

    public function scopePajak($query)
    {
        return $query->where('type', 'pajak');
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isLayanan()
    {
        return $this->type === 'layanan';
    }

    public function isPajak()
    {
        return $this->type === 'pajak';
    }

    public function calculateTax($amount)
    {
        if (!$this->isActive()) {
            return 0;
        }

        return ($amount * $this->value) / 100;
    }
}
