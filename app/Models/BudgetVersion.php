<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_period_id', 'department_id', 'version_number',
        'status', 'submission_notes', 'submitted_by', 'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public static function maxVersions(): int
    {
        return (int) \App\Models\SystemSetting::get('max_budget_versions', 4);
    }

    const STATUS_DRAFT       = 'draft';
    const STATUS_SUBMITTED   = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED    = 'approved';
    const STATUS_REJECTED    = 'rejected';

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function lineItems()
    {
        return $this->hasMany(BudgetLineItem::class);
    }

    public function approvalDecisions()
    {
        return $this->hasMany(ApprovalDecision::class);
    }

    // Total budget value across all line items
    public function totalAmount(): float
    {
        return $this->lineItems()->sum('total_amount');
    }

    // Check if this version can still be edited
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    // Check if dept has hit the version cap
    public static function canCreateNew(int $periodId, int $departmentId): bool
    {
        $count = self::where('budget_period_id', $periodId)
                    ->where('department_id', $departmentId)
                    ->count();

        return $count < self::maxVersions();
    }

    // Create the next version number for a dept in a period
    public static function nextVersionNumber(int $periodId, int $departmentId): int
    {
        $last = self::where('budget_period_id', $periodId)
                    ->where('department_id', $departmentId)
                    ->max('version_number');

        return ($last ?? 0) + 1;
    }


    public function effectiveTotal(): float
    {
        return $this->lineItems->sum(fn($item) => $item->effectiveBudget());
    }
}
