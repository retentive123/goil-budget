<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomBudgetField extends Model
{
    protected $fillable = [
        'name', 'label', 'field_type', 'options',
        'is_required', 'is_active', 'display_order', 'placeholder',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function values()
    {
        return $this->hasMany(BudgetLineItemCustomValue::class, 'custom_budget_field_id');
    }
}
