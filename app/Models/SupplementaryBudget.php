<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SupplementaryBudget extends Model
{
    use LogsActivity;

    protected $fillable = [
        'batch_id',
        'budget_period_id','department_id','budget_line_item_id',
        'account_code_id','line_type',
        'original_amount','requested_amount',
        'justification','supporting_evidence',
        'requested_by',
    ];

    protected $casts = [
        'original_amount'  => 'float',
        'requested_amount' => 'float',
        'approved_amount'  => 'float',
        'submitted_at'     => 'datetime',
        'reviewed_at'      => 'datetime',
        'approved_at'      => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function lineItem()
    {
        return $this->belongsTo(BudgetLineItem::class, 'budget_line_item_id');
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function totalAfterSupplementary(): float
    {
        return $this->original_amount + ($this->approved_amount ?? $this->requested_amount);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status','requested_amount','approved_amount'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn($e) => "Supplementary budget {$e}");
    }
}
