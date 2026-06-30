<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'employee_id',
        'phone',
        'is_active',
        'last_login_at',
        'password_changed_at',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_enabled'       => 'boolean',
            'two_factor_confirmed_at'  => 'datetime',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedVersions()
    {
        return $this->hasMany(BudgetVersion::class, 'submitted_by');
    }

    public function approvalDecisions()
    {
        return $this->hasMany(ApprovalDecision::class, 'decided_by');
    }

    public function budgetNotifications()
    {
        return $this->hasMany(BudgetNotification::class);
    }

    public function unreadNotifications()
    {
        return $this->budgetNotifications()->whereNull('read_at');
    }
}
