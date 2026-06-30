<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineItemApproval extends Model
{
    protected $fillable = [
        'approval_decision_id', 'budget_line_item_id',
        'status', 'approved_amount', 'comments'
    ];

    protected $casts = [
        'approved_amount' => 'float',
    ];

    public function approvalDecision()
    {
        return $this->belongsTo(ApprovalDecision::class);
    }

    public function lineItem()
    {
        return $this->belongsTo(BudgetLineItem::class, 'budget_line_item_id');
    }
}
