<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'year', 'start_date', 'end_date',
        'status', 'opened_at', 'closed_at', 'created_by', 'entry_mode',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'opened_at'  => 'datetime',
        'closed_at'  => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Per-period calc settings (snapshot of rate/freq mode for this period) ──
    public function setting()
    {
        return $this->hasOne(BudgetPeriodSetting::class, 'budget_period_id');
    }

    // ── Per-period rate snapshots for account codes and categories ──
    public function codeRates()
    {
        return $this->hasMany(BudgetPeriodCodeRate::class, 'budget_period_id');
    }

    public function categoryRates()
    {
        return $this->hasMany(BudgetPeriodCategoryRate::class, 'budget_period_id');
    }

    /**
     * Calc mode for this period. Falls back to global system setting so existing
     * periods without a BudgetPeriodSetting row still work correctly.
     */
    public function calcMode(): string
    {
        return $this->setting?->line_item_calc_mode
            ?? SystemSetting::get('line_item_calc_mode', 'none');
    }

    public function adminSetsRate(): bool
    {
        return $this->setting !== null
            ? (bool) $this->setting->admin_sets_rate
            : (bool) SystemSetting::get('admin_sets_rate', false);
    }

    public function adminSetsFreq(): bool
    {
        return $this->setting !== null
            ? (bool) $this->setting->admin_sets_freq
            : (bool) SystemSetting::get('admin_sets_freq', false);
    }

    public function budgetVersions()
    {
        return $this->hasMany(BudgetVersion::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'approved']);
    }

    public function isMonthly(): bool
    {
        return $this->entry_mode === 'monthly';
    }

    public function hasEntries(): bool
    {
        return $this->budgetVersions()
            ->whereHas('lineItems', fn($q) => $q->where('total_amount', '>', 0))
            ->exists();
    }

    // Get the single currently open period (there should only ever be one)
    public static function current(): ?self
    {
        return self::where('status', 'open')->latest()->first();
    }
}
