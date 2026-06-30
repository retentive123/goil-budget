<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionDeadlineOverride extends Model
{
    protected $fillable = [
        'budget_period_id','department_id',
        'granted_by','requested_by',
        'reason','new_deadline','is_active','expires_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'new_deadline' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }
}
