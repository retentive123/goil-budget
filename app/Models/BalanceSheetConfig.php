<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceSheetConfig extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function lines()
    {
        return $this->hasMany(BalanceSheetLine::class)->orderBy('sort_order');
    }
}
