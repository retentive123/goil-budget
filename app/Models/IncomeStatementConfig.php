<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeStatementConfig extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function lines()
    {
        return $this->hasMany(IncomeStatementLine::class)->orderBy('sort_order');
    }
}
