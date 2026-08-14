<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPeriodSetting extends Model
{
    protected $fillable = [
        'budget_period_id',
        'line_item_calc_mode',
        'admin_sets_rate',
        'admin_sets_freq',
    ];

    protected $casts = [
        'admin_sets_rate' => 'boolean',
        'admin_sets_freq' => 'boolean',
    ];

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }
}
