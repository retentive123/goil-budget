<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_category_id', 'code', 'name', 'description',
        'unit', 'calc_type', 'calc_config', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'calc_config' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_account_codes')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    public function lineItems()
    {
        return $this->hasMany(BudgetLineItem::class);
    }
}
