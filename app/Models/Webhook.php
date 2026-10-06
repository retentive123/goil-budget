<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'name', 'url', 'events', 'secret', 'is_active',
        'last_fired_at', 'last_status_code', 'last_response',
    ];

    protected $casts = [
        'events'        => 'array',
        'is_active'     => 'boolean',
        'last_fired_at' => 'datetime',
    ];

    /** Active webhooks that listen for a given event. */
    public static function forEvent(string $event): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();
    }
}
