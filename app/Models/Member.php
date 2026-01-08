<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Member extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'phone', 'total_points'];

    public function pointHistories()
    {
        return $this->hasMany(MemberPointHistory::class);
    }
}
