<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountSubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'budget_type', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function categories()
    {
        return $this->hasMany(AccountCategory::class);
    }
}
