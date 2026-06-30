<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetNotification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'subject',
        'message', 'notifiable_id', 'notifiable_type', 'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
