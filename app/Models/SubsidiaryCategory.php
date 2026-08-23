<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsidiaryCategory extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function subsidiaries()
    {
        return $this->hasMany(Subsidiary::class);
    }

    public function activeSubsidiaries()
    {
        return $this->hasMany(Subsidiary::class)->where('is_active', true);
    }
}
