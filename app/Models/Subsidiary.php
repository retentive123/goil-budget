<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subsidiary extends Model
{
    protected $fillable = [
        'subsidiary_category_id', 'name', 'code',
        'description', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    // ── Relationships ──────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(SubsidiaryCategory::class, 'subsidiary_category_id');
    }

    public function accountCodes()
    {
        return $this->belongsToMany(AccountCode::class, 'subsidiary_account_codes')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function budgetVersions()
    {
        return $this->hasMany(BudgetVersion::class);
    }

    public function budgetActuals()
    {
        return $this->hasMany(BudgetActual::class);
    }

    // ── Helpers (mirror Department's budget helpers) ───────────────────────

    public static function canCreateNew(int $periodId, int $subsidiaryId): bool
    {
        $count = BudgetVersion::where('budget_period_id', $periodId)
                              ->where('subsidiary_id', $subsidiaryId)
                              ->count();

        return $count < BudgetVersion::maxVersions();
    }

    public static function nextVersionNumber(int $periodId, int $subsidiaryId): int
    {
        $last = BudgetVersion::where('budget_period_id', $periodId)
                             ->where('subsidiary_id', $subsidiaryId)
                             ->max('version_number');

        return ($last ?? 0) + 1;
    }
}
