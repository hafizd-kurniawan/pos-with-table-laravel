<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\Order;

class MemberPointHistory extends Model
{
    protected $fillable = ['member_id', 'order_id', 'type', 'points', 'description'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
