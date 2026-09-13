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
// POINT 9 — AUDIT LOG RETENTION
// Keeps critical events forever. Info logs pruned after 6 months,
// warning logs after 24 months. Periods are configurable in System Settings.
// ══════════════════════════════════════════════════════════════════════════════

Artisan::command('audit:prune', function () {
    $infoMonths    = (int) SystemSetting::get('audit_retain_info_months',    6);
    $warningMonths = (int) SystemSetting::get('audit_retain_warning_months', 24);

    $deletedInfo    = SystemAuditLog::where('severity', 'info')
                        ->where('created_at', '<', now()->subMonths($infoMonths))
                        ->delete();

    $deletedWarning = SystemAuditLog::where('severity', 'warning')
                        ->where('created_at', '<', now()->subMonths($warningMonths))
                        ->delete();

    // Critical events are never auto-deleted

    $this->info("Audit log pruned — {$deletedInfo} info records (>{$infoMonths}mo) and {$deletedWarning} warning records (>{$warningMonths}mo) removed. Critical events retained.");
})->purpose('Prune old audit log entries — critical events are kept forever');

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
