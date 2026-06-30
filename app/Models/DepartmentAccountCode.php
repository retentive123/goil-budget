<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentAccountCode extends Model
{
    protected $fillable = ['department_id', 'account_code_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }
}
