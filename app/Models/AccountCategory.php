<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active', 'budget_type'];

    protected $casts = ['is_active' => 'boolean'];

    public function isRevenue(): bool  {
        return in_array($this->budget_type, ['revenue','both']);
        }

    public function isExpense(): bool  {
        return in_array($this->budget_type, ['expense','both']);
        }

    public function accountCodes()
    {
        return $this->hasMany(AccountCode::class);
    }
}
