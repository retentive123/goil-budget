<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapexConfig extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function lines()
    {
        return $this->hasMany(CapexLine::class)->orderBy('sort_order');
    }
}
