<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalDecision extends Model
{
    protected $fillable = [
        'budget_version_id', 'approval_stage_id',
        'decided_by', 'decision', 'comments', 'decided_at'
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function budgetVersion()
    {
        return $this->belongsTo(BudgetVersion::class);
    }

    public function stage()
    {
        return $this->belongsTo(ApprovalStage::class, 'approval_stage_id');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function lineItemApprovals()
    {
        return $this->hasMany(LineItemApproval::class);
    }
}
