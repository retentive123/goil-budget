<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStage extends Model
{
    protected $fillable = ['name', 'order', 'role_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function decisions()
    {
        return $this->hasMany(ApprovalDecision::class);
    }

    public static function ordered()
    {
        return self::where('is_active', true)->orderBy('order')->get();
    }

    // Get the next stage after this one
    public function nextStage(): ?self
    {
        return self::where('order', '>', $this->order)
                   ->where('is_active', true)
                   ->orderBy('order')
                   ->first();
    }
}
