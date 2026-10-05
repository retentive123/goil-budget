<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatioConfig extends Model
{
    protected $fillable = [
        'name', 'description',
        'numerator_source', 'numerator_types', 'numerator_codes', 'numerator_category_ids',
        'denominator_source', 'denominator_types', 'denominator_codes', 'denominator_category_ids',
        'multiply_by', 'unit', 'higher_is_better',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'numerator_types'          => 'array',
        'numerator_codes'          => 'array',
        'numerator_category_ids'   => 'array',
        'denominator_types'        => 'array',
        'denominator_codes'        => 'array',
        'denominator_category_ids' => 'array',
        'multiply_by'       => 'float',
        'higher_is_better'  => 'boolean',
        'is_active'         => 'boolean',
    ];

    public static function allTypes(): array
    {
        return [
            'all'                 => 'All Categories',
            'revenue'             => 'Revenue',
            'expense'             => 'Expense',
            'both'                => 'Revenue & Expense',
            'capital_expenditure' => 'Capital Expenditure',
            'assets'              => 'Assets',
            'liabilities'         => 'Liabilities',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
