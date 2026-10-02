<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatioConfig extends Model
{
    protected $fillable = [
        'name', 'description',
        'numerator_source', 'numerator_types',
        'denominator_source', 'denominator_types',
        'multiply_by', 'unit', 'higher_is_better',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'numerator_types'   => 'array',
        'denominator_types' => 'array',
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
