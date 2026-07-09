<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_version_id', 'account_code_id',
        'm1_amount',  'm2_amount',  'm3_amount',  'm4_amount',
        'm5_amount',  'm6_amount',  'm7_amount',  'm8_amount',
        'm9_amount',  'm10_amount', 'm11_amount', 'm12_amount',
        'justification', 'last_updated_by', 'line_type',
    ];

    protected $casts = [
        'm1_amount'  => 'float', 'm2_amount'  => 'float', 'm3_amount'  => 'float',
        'm4_amount'  => 'float', 'm5_amount'  => 'float', 'm6_amount'  => 'float',
        'm7_amount'  => 'float', 'm8_amount'  => 'float', 'm9_amount'  => 'float',
        'm10_amount' => 'float', 'm11_amount' => 'float', 'm12_amount' => 'float',
        'total_amount' => 'float',
    ];

    // Backward-compat quarterly accessors — quarters derived from monthly storage
    public function getQ1AmountAttribute(): float { return $this->m1_amount  + $this->m2_amount  + $this->m3_amount;  }
    public function getQ2AmountAttribute(): float { return $this->m4_amount  + $this->m5_amount  + $this->m6_amount;  }
    public function getQ3AmountAttribute(): float { return $this->m7_amount  + $this->m8_amount  + $this->m9_amount;  }
    public function getQ4AmountAttribute(): float { return $this->m10_amount + $this->m11_amount + $this->m12_amount; }

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
    // quarter = 'q1'|'q2'|'q3'|'q4' — uses accessor which sums 3 monthly columns
    $supplementary = $quarter === 'q4' ? $this->approvedSupplementaryTotal() : 0;
    return (float) $this->{"{$quarter}_amount"} + $supplementary;
}

}
