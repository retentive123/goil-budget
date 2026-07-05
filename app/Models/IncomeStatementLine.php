<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeStatementLine extends Model
{
    protected $fillable = [
        'income_statement_config_id',
        'line_type',
        'sub_category_id',
        'label',
        'operator',
        'sort_order',
        'cs_base_sub_category_id',
        'cs_base_subtotal_label',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function config()
    {
        return $this->belongsTo(IncomeStatementConfig::class, 'income_statement_config_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(AccountSubCategory::class, 'sub_category_id');
    }

    public function csBase()
    {
        return $this->belongsTo(AccountSubCategory::class, 'cs_base_sub_category_id');
    }

    public function displayLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }
        if ($this->line_type === 'sub_category' && $this->subCategory) {
            $prefix = $this->operator === 'subtract' ? 'Less: ' : 'Add: ';
            return $prefix . $this->subCategory->name;
        }
        return $this->label ?? 'Subtotal';
    }
}
