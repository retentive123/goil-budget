<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Virement extends Model
{
    protected $fillable = [
        'budget_period_id', 'department_id',
        'from_line_item_id', 'to_line_item_id',
        'amount', 'justification', 'status',
        'requested_by', 'approved_by',
        'approval_comments', 'approved_at'
    ];

    protected $casts = [
        'amount'      => 'float',
        'approved_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function fromLineItem()
    {
        return $this->belongsTo(BudgetLineItem::class, 'from_line_item_id');
    }

    public function toLineItem()
    {
        return $this->belongsTo(BudgetLineItem::class, 'to_line_item_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Validate the 10% rule before saving
    public function isWithinVirementLimit(): bool
    {
        return $this->amount <= $this->fromLineItem->maxVirementAmount();
    }
}
