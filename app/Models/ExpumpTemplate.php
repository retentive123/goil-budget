<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpumpTemplate extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function values()
    {
        return $this->hasMany(ExpumpValue::class, 'expump_template_id');
    }
}
