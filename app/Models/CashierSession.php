<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class CashierSession extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'starting_cash',
        'ending_cash',
        'cash_sales',
        'cash_refunds',
        'total_pay_in',
        'total_pay_out',
        'expected_ending_cash',
        'variance',
        'status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'starting_cash' => 'decimal:2',
        'ending_cash' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
        'total_pay_in' => 'decimal:2',
        'total_pay_out' => 'decimal:2',
        'expected_ending_cash' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(CashierSessionTransaction::class);
    }
}
