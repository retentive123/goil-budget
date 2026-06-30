<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_version_id', 'account_code_id',
        'q1_amount', 'q2_amount', 'q3_amount', 'q4_amount',
        'justification', 'last_updated_by', 'line_type',
    ];

    protected $casts = [
        'q1_amount' => 'float',
        'q2_amount' => 'float',
        'q3_amount' => 'float',
        'q4_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function budgetVersion()
    {
        return $this->belongsTo(BudgetVersion::class);
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function lineItemApprovals()
    {
        return $this->hasMany(LineItemApproval::class);
    }

    // 10% virement default rule: amount being moved cannot exceed 10% of this line's total
   public function maxVirementAmount(): float
    {
        $pct = (int) \App\Models\SystemSetting::get('virement_limit_pct', 10);
        return round($this->total_amount * ($pct / 100), 2);
    }

    public function actuals()
{
    return $this->hasMany(BudgetActual::class);
}

public function actualTotal(): float
{
    return $this->actuals()->where('status','confirmed')->sum('amount');
}

public function actualForMonth(int $month, int $year): ?BudgetActual
{
    return $this->actuals()
                ->where('month', $month)
                ->where('year',  $year)
                ->first();
}

public function approvedSupplementaryTotal(): float
{
    return (float) \App\Models\SupplementaryBudget::where('budget_line_item_id', $this->id)
        ->where('status', 'approved')
        ->sum('approved_amount');
}

public function effectiveBudget(): float
{
    return (float) $this->total_amount + $this->approvedSupplementaryTotal();
}

public function effectiveQuarter(string $quarter): float
{
    // quarter = 'q1'|'q2'|'q3'|'q4' — supplementary is treated as part of Q4 bucket for cap purposes
    $supplementary = $quarter === 'q4' ? $this->approvedSupplementaryTotal() : 0;
    return (float) $this->{"{$quarter}_amount"} + $supplementary;
}

}
