<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Mail;
use App\Models\SystemAuditLog;
use App\Models\SystemSetting;
use App\Models\BudgetPeriod;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\BudgetNotification;
use App\Models\User;
use App\Http\Controllers\Admin\SystemSettingController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ══════════════════════════════════════════════════════════════════════════════
// BACKUPS
// ══════════════════════════════════════════════════════════════════════════════

Schedule::command('backup:run --type=scheduled')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->emailOutputOnFailure(config('mail.from.address'));

// Weekly full backup on Sunday at 1am
Schedule::command('backup:run --type=scheduled')
    ->weekly()
    ->sundays()
    ->at('01:00')
    ->withoutOverlapping();

// ══════════════════════════════════════════════════════════════════════════════
// APPROVER REMINDERS
// Runs daily. Sends in-app + email reminders to approvers who still have
// pending budgets, based on the approver_reminder_mode setting.
// ══════════════════════════════════════════════════════════════════════════════

Artisan::command('budget:remind-approvers', function () {
    $mode = SystemSetting::get('approver_reminder_mode', 'off');

    if ($mode === 'off') {
        $this->info('Approver reminders are disabled (mode = off).');
        return;
    }

    if ($mode !== 'auto') {
        $this->info("Approver reminder mode is '{$mode}' — skipping automated send.");
        return;
    }

    $freqDays = max(1, (int) SystemSetting::get('approver_reminder_frequency_days', 3));

    // Only send if today is a multiple of the frequency since a known epoch
    // Simple approach: check if today's day-of-year mod freqDays === 0
    $dayOfYear = (int) now()->format('z') + 1;
    if ($dayOfYear % $freqDays !== 0 && $freqDays > 1) {
        $this->info("Approver reminders: not due today (every {$freqDays} days, today = day {$dayOfYear}).");
        return;
    }

    $sent = SystemSettingController::dispatchApproverReminders();

    $this->info("Approver reminders sent — {$sent} notification(s) created.");
})->purpose('Send approval reminder notifications to pending approvers (controlled by settings)');

Schedule::command('budget:remind-approvers')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// ══════════════════════════════════════════════════════════════════════════════
// POINT 9 — AUDIT LOG RETENTION
// Configurable retention per severity via System Settings.
// ══════════════════════════════════════════════════════════════════════════════

Artisan::command('audit:prune', function () {
    $infoMonths    = (int) SystemSetting::get('audit_retain_info_months',    24);
    $warningMonths = (int) SystemSetting::get('audit_retain_warning_months', 24);
    $keepCritical  = (bool) SystemSetting::get('audit_log_keep_critical', true);

    $deletedInfo    = SystemAuditLog::where('severity', 'info')
                        ->where('created_at', '<', now()->subMonths($infoMonths))
                        ->delete();

    $deletedWarning = SystemAuditLog::where('severity', 'warning')
                        ->where('created_at', '<', now()->subMonths($warningMonths))
                        ->delete();

    $deletedCritical = 0;
    if (!$keepCritical) {
        $retainMonths    = max($infoMonths, $warningMonths);
        $deletedCritical = SystemAuditLog::where('severity', 'critical')
                            ->where('created_at', '<', now()->subMonths($retainMonths))
                            ->delete();
    }

    $criticalNote = $keepCritical ? 'Critical events retained forever.' : "{$deletedCritical} critical record(s) also pruned.";

    $this->info(
        "Audit log pruned — {$deletedInfo} info (>{$infoMonths}mo), "
        . "{$deletedWarning} warning (>{$warningMonths}mo). {$criticalNote}"
    );
})->purpose('Prune old audit log entries per retention settings');

Schedule::command('audit:prune')
    ->monthlyOn(1, '03:00')   // 1st of each month at 03:00
    ->withoutOverlapping();

// ══════════════════════════════════════════════════════════════════════════════
// POINT 10 — QUEUE HEALTH
// Prunes stale failed jobs (keeps last 72 hours of failures for inspection).
// ══════════════════════════════════════════════════════════════════════════════

Schedule::command('queue:prune-failed', ['--hours' => 72])
    ->dailyAt('04:00');

// ══════════════════════════════════════════════════════════════════════════════
// DEADLINE REMINDER EMAILS
// Runs daily at 08:00. Sends reminders on the 7-day and 1-day mark to any
// department that hasn't submitted their budget for the current period.
// ══════════════════════════════════════════════════════════════════════════════

Artisan::command('budget:send-deadline-reminders', function () {
    $period = BudgetPeriod::current();

    if (!$period || !$period->end_date) {
        $this->info('No active period or deadline not set — nothing to do.');
        return;
    }

    $daysLeft = (int) now()->startOfDay()->diffInDays($period->end_date->startOfDay(), false);

    // Only act on the 7-day and 1-day warnings
    if (!in_array($daysLeft, [7, 1])) {
        $this->info("Deadline is in {$daysLeft} day(s) — no reminder sent today.");
        return;
    }

    $label    = $daysLeft === 1 ? 'tomorrow' : 'in 7 days';
    $deadline = $period->end_date->format('d M Y');

    // Departments that have already submitted, are under review, or are approved
    $submittedDeptIds = BudgetVersion::where('budget_period_id', $period->id)
        ->whereIn('status', ['submitted', 'under_review', 'approved'])
        ->pluck('department_id')
        ->filter()
        ->unique();

    // Active departments that haven't submitted
    $pendingDepts = Department::where('is_active', true)
        ->whereNotIn('id', $submittedDeptIds)
        ->with(['users' => fn($q) => $q->where('is_active', true)])
        ->get();

    $emailsSent = 0;

    foreach ($pendingDepts as $dept) {
        foreach ($dept->users as $user) {
            // In-app notification (de-dup: skip if one already sent today for this user+period)
            $alreadyNotified = BudgetNotification::where('user_id', $user->id)
                ->where('type', 'deadline_reminder')
                ->where('notifiable_id', $period->id)
                ->whereDate('created_at', today())
                ->exists();

            if (!$alreadyNotified) {
                BudgetNotification::create([
                    'user_id'         => $user->id,
                    'type'            => 'deadline_reminder',
                    'subject'         => "Budget deadline {$label} — {$period->name}",
                    'message'         => "Your department's budget for {$period->name} has not been submitted yet. The deadline is {$deadline} ({$label}). Please submit as soon as possible.",
                    'notifiable_id'   => $period->id,
                    'notifiable_type' => BudgetPeriod::class,
                ]);
            }

            // Email
            try {
                Mail::raw(
                    "Dear {$user->name},\n\n"
                    . "⚠️  BUDGET SUBMISSION REMINDER\n\n"
                    . "This is to inform you that the budget submission deadline for {$period->name} "
                    . "is on {$deadline} ({$label}).\n\n"
                    . "Your department ({$dept->name}) has not yet submitted a budget for this period.\n\n"
                    . "Please log in to the GOIL Budget System and submit your budget before the deadline.\n\n"
                    . "This is an automated reminder from the GOIL Budget System.",
                    fn($msg) => $msg
                        ->to($user->email, $user->name)
                        ->subject("⚠️ Budget deadline {$label} — {$period->name}")
                );
                $emailsSent++;
            } catch (\Exception $e) {
                $this->warn("Could not send email to {$user->email}: " . $e->getMessage());
            }
        }
    }

    $this->info(
        "Deadline reminders sent — {$pendingDepts->count()} department(s) pending, "
        . "{$emailsSent} email(s) sent. Period: {$period->name}, deadline: {$deadline}."
    );
})->purpose('Send budget deadline reminder emails to departments that have not yet submitted');

Schedule::command('budget:send-deadline-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();
