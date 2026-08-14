<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPeriodCodeRate extends Model
{
    protected $fillable = [
        'budget_period_id',
        'account_code_id',
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

    public function code()
    {
        return $this->belongsTo(AccountCode::class, 'account_code_id');
    }
}
