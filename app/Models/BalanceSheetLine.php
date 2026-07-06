<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceSheetLine extends Model
{
    protected $fillable = [
        'balance_sheet_config_id',
        'line_type',
        'sub_category_id',
        'label',
        'section',
        'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function config()
    {
        return $this->belongsTo(BalanceSheetConfig::class, 'balance_sheet_config_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(AccountSubCategory::class, 'sub_category_id');
    }
}
