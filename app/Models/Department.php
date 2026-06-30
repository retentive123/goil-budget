<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active', 'budget_type'];

    protected $casts = ['is_active' => 'boolean'];

    public function isRevenueGenerating(): bool {
        return in_array($this->budget_type, ['revenue','both']);
        }

    public function isExpenseDepartment(): bool  {
        return in_array($this->budget_type, ['expense','both']);
        }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function accountCodes()
    {
        return $this->belongsToMany(AccountCode::class, 'department_account_codes')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    public function budgetVersions()
    {
        return $this->hasMany(BudgetVersion::class);
    }

    public function virements()
    {
        return $this->hasMany(Virement::class);
    }

    // Get the active budget version for a given period
    public function activeBudgetVersion($budgetPeriodId)
    {
        return $this->budgetVersions()
                    ->where('budget_period_id', $budgetPeriodId)
                    ->orderByDesc('version_number')
                    ->first();
    }
}
