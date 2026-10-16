<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStage extends Model
{
    protected $fillable = ['name', 'order', 'role_name', 'route_key', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function decisions()
    {
        return $this->hasMany(ApprovalDecision::class);
    }

    public static function ordered()
    {
        return self::where('is_active', true)->orderBy('order')->get();
    }

    // Route values a department can have
    public static array $routes = [
        'gceo'   => 'Via GCEO / MD',
        'coo'    => 'Via COO',
        'direct' => 'Direct to Board (skip middle)',
    ];

    // Get the next stage after this one, respecting the department's approval route
    public function nextStage(?string $route = 'gceo'): ?self
    {
        return self::where('order', '>', $this->order)
                   ->where('is_active', true)
                   ->where(function ($q) use ($route) {
                       $q->whereNull('route_key')
                         ->orWhere('route_key', $route);
                   })
                   ->orderBy('order')
                   ->first();
    }
}
