<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPeriodCategoryRate extends Model
{
    protected $fillable = [
        'budget_period_id',
        'account_category_id',
        'default_rate',
        'default_frequency',
    ];

    protected $casts = [
        'default_rate'      => 'float',
        'default_frequency' => 'float',
    ];

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function category()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }
}
