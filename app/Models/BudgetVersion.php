<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_period_id', 'department_id', 'subsidiary_id', 'version_number',
        'status', 'submission_notes', 'submitted_by', 'submitted_at',
        'is_revision', 'revised_from_id', 'revision_notes', 'revised_at', 'revised_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'revised_at'   => 'datetime',
        'is_revision'  => 'boolean',
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

    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }

    /**
     * The entity (Department or Subsidiary) that owns this version.
     * Returns the model instance or null.
     */
    public function owner(): Department|Subsidiary|null
    {
        return $this->department ?? $this->subsidiary;
    }

    /** Display name: department name or subsidiary name. */
    public function ownerName(): string
    {
        return $this->department?->name ?? $this->subsidiary?->name ?? '—';
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** The original approved budget this version is a revision of. */
    public function originalVersion()
    {
        return $this->belongsTo(BudgetVersion::class, 'revised_from_id');
    }

    /** All revisions created from this budget version. */
    public function revisions()
    {
        return $this->hasMany(BudgetVersion::class, 'revised_from_id');
    }

    public function revisedBy()
    {
        return $this->belongsTo(User::class, 'revised_by');
    }

    /** True when this version is an approved revision. */
    public function isApprovedRevision(): bool
    {
        return $this->is_revision && $this->status === self::STATUS_APPROVED;
    }

    /**
     * For a given period + owner, find the latest approved original (non-revision) version.
     */
    public static function latestApprovedOriginal(int $periodId, ?int $deptId, ?int $subId): ?self
    {
        return self::where('budget_period_id', $periodId)
            ->where('is_revision', false)
            ->where('status', self::STATUS_APPROVED)
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($subId,  fn($q) => $q->where('subsidiary_id',  $subId))
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * For a given period + owner, find the latest approved revision (if any).
     */
    public static function latestApprovedRevision(int $periodId, ?int $deptId, ?int $subId): ?self
    {
        return self::where('budget_period_id', $periodId)
            ->where('is_revision', true)
            ->where('status', self::STATUS_APPROVED)
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($subId,  fn($q) => $q->where('subsidiary_id',  $subId))
            ->orderByDesc('version_number')
            ->first();
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

    // Check if a dept/subsidiary has hit the version cap (revisions are exempt)
    public static function canCreateNew(int $periodId, int $departmentId = null, int $subsidiaryId = null): bool
    {
        $count = self::where('budget_period_id', $periodId)
                    ->where('is_revision', false)   // revisions don't count against the cap
                    ->when($departmentId,  fn($q) => $q->where('department_id',  $departmentId))
                    ->when($subsidiaryId,  fn($q) => $q->where('subsidiary_id',  $subsidiaryId))
                    ->count();

        return $count < self::maxVersions();
    }

    // Next version number for a dept or subsidiary in a period
    public static function nextVersionNumber(int $periodId, int $departmentId = null, int $subsidiaryId = null): int
    {
        $last = self::where('budget_period_id', $periodId)
                    ->when($departmentId,  fn($q) => $q->where('department_id',  $departmentId))
                    ->when($subsidiaryId,  fn($q) => $q->where('subsidiary_id',  $subsidiaryId))
                    ->max('version_number');

        return ($last ?? 0) + 1;
    }


    public function effectiveTotal(): float
    {
        return $this->lineItems->sum(fn($item) => $item->effectiveBudget());
    }
}
