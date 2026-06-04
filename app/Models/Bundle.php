<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $guarded = [];

    public function tryouts()
    {
        return $this->belongsToMany(Tryout::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
