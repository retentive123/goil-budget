<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active', 'budget_type', 'account_sub_category_id'];

    protected $casts = ['is_active' => 'boolean'];

    // Types that appear in the P&L income statement
    public const PNL_TYPES = ['revenue', 'expense', 'both'];

    public function isRevenue(): bool {
        return in_array($this->budget_type, ['revenue', 'both']);
    }

    public function isExpense(): bool {
        return in_array($this->budget_type, ['expense', 'both']);
    }

    public function isCapex(): bool {
        return $this->budget_type === 'capital_expenditure';
    }

    public function isAsset(): bool {
        return $this->budget_type === 'assets';
    }

    public function isLiability(): bool {
        return $this->budget_type === 'liabilities';
    }

    public function isPnlType(): bool {
        return in_array($this->budget_type, self::PNL_TYPES);
    }

    public function subCategory()
    {
        return $this->belongsTo(AccountSubCategory::class, 'account_sub_category_id');
    }

    public function accountCodes()
    {
        return $this->hasMany(AccountCode::class);
    }
}
