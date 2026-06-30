<?php

namespace App\Services;

use App\Models\SystemAuditLog;
use App\Models\BudgetVersion;
use App\Models\Virement;
use App\Models\User;

class AuditLogger
{

public static function record(
        string $event,
        string $module,
        string $action,
        array $options = []
    ): void
    {
        SystemAuditLog::record(
            event: $event,
            module: $module,
            action: $action,
            options: $options
        );
    }
    // ── Auth events ──────────────────────────────────
    public static function login(User $user): void
    {
        SystemAuditLog::record(
            event:  'user_login',
            module: 'auth',
            action: 'login',
            options: [
                'user_id'       => $user->id,
                'subject_type'  => User::class,
                'subject_id'    => $user->id,
                'subject_label' => $user->name,
                'meta'          => ['email' => $user->email],
                'severity'      => 'info',
            ]
        );
    }

    public static function logout(User $user): void
    {
        SystemAuditLog::record(
            event:  'user_logout',
            module: 'auth',
            action: 'logout',
            options: [
                'user_id'       => $user->id,
                'subject_type'  => User::class,
                'subject_id'    => $user->id,
                'subject_label' => $user->name,
                'severity'      => 'info',
            ]
        );
    }

    public static function loginFailed(string $email): void
    {
        SystemAuditLog::record(
            event:  'login_failed',
            module: 'auth',
            action: 'login_failed',
            options: [
                'user_id'       => null,
                'subject_label' => $email,
                'meta'          => ['email' => $email],
                'severity'      => 'warning',
            ]
        );
    }

    public static function passwordChanged(User $user): void
    {
        SystemAuditLog::record(
            event:  'password_changed',
            module: 'auth',
            action: 'updated',
            options: [
                'user_id'       => $user->id,
                'subject_type'  => User::class,
                'subject_id'    => $user->id,
                'subject_label' => $user->name,
                'severity'      => 'warning',
            ]
        );
    }

    // ── Budget events ─────────────────────────────────
    public static function budgetCreated(BudgetVersion $v): void
    {
        SystemAuditLog::record(
            event:  'budget_created',
            module: 'budget',
            action: 'created',
            options: [
                'subject_type'  => BudgetVersion::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} v{$v->version_number} — {$v->period->name}",
                'new_values'    => ['status' => $v->status, 'version' => $v->version_number],
                'severity'      => 'info',
            ]
        );
    }

    public static function budgetSubmitted(BudgetVersion $v): void
    {
        SystemAuditLog::record(
            event:  'budget_submitted',
            module: 'budget',
            action: 'submitted',
            options: [
                'subject_type'  => BudgetVersion::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} v{$v->version_number} — {$v->period->name}",
                'new_values'    => [
                    'total' => $v->lineItems()->sum('total_amount'),
                    'notes' => $v->submission_notes,
                ],
                'severity'      => 'info',
            ]
        );
    }

    public static function budgetApproved(BudgetVersion $v, string $stageName): void
    {
        SystemAuditLog::record(
            event:  'budget_approved',
            module: 'approval',
            action: 'approved',
            options: [
                'subject_type'  => BudgetVersion::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} v{$v->version_number}",
                'meta'          => ['stage' => $stageName],
                'severity'      => 'info',
            ]
        );
    }

    public static function budgetRejected(BudgetVersion $v, string $stageName, string $comments): void
    {
        SystemAuditLog::record(
            event:  'budget_rejected',
            module: 'approval',
            action: 'rejected',
            options: [
                'subject_type'  => BudgetVersion::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} v{$v->version_number}",
                'meta'          => ['stage' => $stageName, 'comments' => $comments],
                'severity'      => 'warning',
            ]
        );
    }

    // ── Virement events ───────────────────────────────
    public static function virementRequested(Virement $v): void
    {
        SystemAuditLog::record(
            event:  'virement_requested',
            module: 'virement',
            action: 'created',
            options: [
                'subject_type'  => Virement::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} — {{ currency() }} " . number_format($v->amount, 2),
                'new_values'    => [
                    'from' => $v->fromLineItem->accountCode->code,
                    'to'   => $v->toLineItem->accountCode->code,
                    'amount' => $v->amount,
                ],
                'severity'      => 'info',
            ]
        );
    }

    public static function virementApproved(Virement $v): void
    {
        SystemAuditLog::record(
            event:  'virement_approved',
            module: 'virement',
            action: 'approved',
            options: [
                'subject_type'  => Virement::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} — {{ currency() }} " . number_format($v->amount, 2),
                'severity'      => 'info',
            ]
        );
    }

    public static function virementRejected(Virement $v): void
    {
        SystemAuditLog::record(
            event:  'virement_rejected',
            module: 'virement',
            action: 'rejected',
            options: [
                'subject_type'  => Virement::class,
                'subject_id'    => $v->id,
                'subject_label' => "{$v->department->name} — {{ currency() }} " . number_format($v->amount, 2),
                'severity'      => 'warning',
            ]
        );
    }

    // ── Admin events ──────────────────────────────────
    public static function userCreated(User $target, User $by): void
    {
        SystemAuditLog::record(
            event:  'user_created',
            module: 'admin',
            action: 'created',
            options: [
                'user_id'       => $by->id,
                'subject_type'  => User::class,
                'subject_id'    => $target->id,
                'subject_label' => $target->name,
                'new_values'    => [
                    'email'      => $target->email,
                    'department' => $target->department?->name,
                ],
                'severity'      => 'info',
            ]
        );
    }

    public static function userDeactivated(User $target, User $by): void
    {
        SystemAuditLog::record(
            event:  'user_deactivated',
            module: 'admin',
            action: 'updated',
            options: [
                'user_id'       => $by->id,
                'subject_type'  => User::class,
                'subject_id'    => $target->id,
                'subject_label' => $target->name,
                'old_values'    => ['is_active' => true],
                'new_values'    => ['is_active' => false],
                'severity'      => 'warning',
            ]
        );
    }

    public static function roleAssigned(User $target, string $role, User $by): void
    {
        SystemAuditLog::record(
            event:  'role_assigned',
            module: 'admin',
            action: 'updated',
            options: [
                'user_id'       => $by->id,
                'subject_type'  => User::class,
                'subject_id'    => $target->id,
                'subject_label' => $target->name,
                'new_values'    => ['role' => $role],
                'severity'      => 'warning',
            ]
        );
    }

    public static function settingsChanged(array $changed, User $by): void
    {
        SystemAuditLog::record(
            event:  'settings_changed',
            module: 'settings',
            action: 'updated',
            options: [
                'user_id'       => $by->id,
                'subject_label' => 'System Settings',
                'new_values'    => $changed,
                'severity'      => 'critical',
            ]
        );
    }

    public static function reportExported(string $type, string $format, User $by): void
    {
        SystemAuditLog::record(
            event:  'report_exported',
            module: 'reports',
            action: 'exported',
            options: [
                'user_id'       => $by->id,
                'subject_label' => strtoupper($type) . ' Report',
                'meta'          => ['type' => $type, 'format' => $format],
                'severity'      => 'info',
            ]
        );
    }

    public static function periodOpened(\App\Models\BudgetPeriod $p, User $by): void
    {
        SystemAuditLog::record(
            event:  'period_opened',
            module: 'admin',
            action: 'updated',
            options: [
                'user_id'       => $by->id,
                'subject_label' => $p->name,
                'new_values'    => ['status' => 'open'],
                'severity'      => 'critical',
            ]
        );
    }

    public static function periodClosed(\App\Models\BudgetPeriod $p, User $by): void
    {
        SystemAuditLog::record(
            event:  'period_closed',
            module: 'admin',
            action: 'updated',
            options: [
                'user_id'       => $by->id,
                'subject_label' => $p->name,
                'new_values'    => ['status' => 'closed'],
                'severity'      => 'critical',
            ]
        );
    }
}
