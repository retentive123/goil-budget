<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountCodeSubEntryTemplate extends Model
{
    protected $fillable = ['account_code_id', 'name', 'sort_order'];

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }
}
