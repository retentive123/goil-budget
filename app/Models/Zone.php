<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function departments()
    {
        return $this->hasMany(Department::class)->where('entity_type', 'department');
    }

    public function serviceStations()
    {
        return $this->hasMany(Department::class)->where('entity_type', 'service_station');
    }

    public function allEntities()
    {
        return $this->hasMany(Department::class);
    }
}
