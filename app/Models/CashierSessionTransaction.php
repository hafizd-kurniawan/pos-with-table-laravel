<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierSessionTransaction extends Model
{
    protected $fillable = [
        'cashier_session_id',
        'type', // 'in', 'out'
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function session()
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }
}
