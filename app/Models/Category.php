<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Category extends Model
{
    use BelongsToTenant;
    
    //
    protected $fillable = ['name', 'description', 'image'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
