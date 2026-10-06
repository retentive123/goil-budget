<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLineItemCustomValue extends Model
{
    protected $fillable = ['budget_line_item_id', 'custom_budget_field_id', 'value'];

    public function lineItem()
    {
        return $this->belongsTo(BudgetLineItem::class);
    }

    public function field()
    {
        return $this->belongsTo(CustomBudgetField::class, 'custom_budget_field_id');
    }
}
