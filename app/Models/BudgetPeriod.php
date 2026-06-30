<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'year', 'start_date', 'end_date',
        'status', 'opened_at', 'closed_at', 'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'opened_at'  => 'datetime',
        'closed_at'  => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function budgetVersions()
    {
        return $this->hasMany(BudgetVersion::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'approved']);
    }

    // Get the single currently open period (there should only ever be one)
    public static function current(): ?self
    {
        return self::where('status', 'open')->latest()->first();
    }
}
