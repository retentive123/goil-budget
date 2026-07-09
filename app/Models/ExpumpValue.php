<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpumpValue extends Model
{
    protected $fillable = ['expump_template_id', 'expump_code_id', 'revenue_code_id', 'value'];

    public function template()
    {
        return $this->belongsTo(ExpumpTemplate::class, 'expump_template_id');
    }

    public function expumpCode()
    {
        return $this->belongsTo(AccountCode::class, 'expump_code_id');
    }

    public function revenueCode()
    {
        return $this->belongsTo(AccountCode::class, 'revenue_code_id');
    }
}
