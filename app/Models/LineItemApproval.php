<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineItemApproval extends Model
{
    protected $fillable = [
        'approval_decision_id', 'budget_line_item_id',
        'status', 'approved_amount', 'comments',
        'm1_amount',  'm2_amount',  'm3_amount',  'm4_amount',
        'm5_amount',  'm6_amount',  'm7_amount',  'm8_amount',
        'm9_amount',  'm10_amount', 'm11_amount', 'm12_amount',
    ];

    protected $casts = [
        'approved_amount' => 'float',
        'm1_amount'  => 'float', 'm2_amount'  => 'float', 'm3_amount'  => 'float',
        'm4_amount'  => 'float', 'm5_amount'  => 'float', 'm6_amount'  => 'float',
        'm7_amount'  => 'float', 'm8_amount'  => 'float', 'm9_amount'  => 'float',
        'm10_amount' => 'float', 'm11_amount' => 'float', 'm12_amount' => 'float',
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
