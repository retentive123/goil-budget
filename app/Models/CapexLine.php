<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapexLine extends Model
{
    protected $fillable = ['capex_config_id', 'line_type', 'sub_category_id', 'label', 'sort_order'];

    public function config()
    {
        return $this->belongsTo(CapexConfig::class, 'capex_config_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(AccountSubCategory::class, 'sub_category_id');
    }
}
