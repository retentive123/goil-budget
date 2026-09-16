<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\BudgetVersion;
use App\Models\BudgetNotification;
use App\Models\ApprovalStage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SystemSettingController extends Controller
{
    public function index()
    {
        // Preferred group display order
        $groupOrder = ['general', 'budget', 'notifications', 'mail', 'security', 'backup'];

        // Preferred key order within each group (keys not listed fall to the end alphabetically)
        $keyOrder = [
            'general' => [
                'app_name', 'company_name', 'currency_symbol', 'fiscal_year_start',
            ],
            'budget' => [
                'max_budget_versions', 'budget_entry_deadline_days',
                // Calculation mode first, then the settings that depend on it
                'line_item_calc_mode', 'admin_sets_rate', 'admin_sets_freq',
                'manual_period_split',
                'require_justification',
                'actuals_budget_check_mode',
                'actuals_approval_flow',
                'allow_revision_of_revision',
                'allow_virement_after_approval', 'virement_limit_pct',
                'supplementary_approval_mode',
            ],
            'notifications' => [
                'email_notifications_enabled',
                'notify_on_submission', 'notify_on_approval', 'notify_on_rejection',
                'notify_on_virement', 'notify_finance_on_virement',
                'approver_reminder_mode', 'approver_reminder_frequency_days',
                'audit_retain_info_months', 'audit_retain_warning_months', 'audit_log_keep_critical',
            ],
            'security' => [
                'sso_enabled', 'sso_ad_server', 'sso_ad_port', 'sso_ad_ssl', 'sso_ad_tls',
                'sso_ad_domain', 'sso_ad_basedn', 'sso_service_account', 'sso_service_password',
                'sso_auto_create_users', 'sso_fallback_to_local',
                'enforce_segregation_of_duties',
                'session_timeout_minutes', 'single_session_only',
                'max_login_attempts', 'login_lockout_minutes',
                'force_password_change_days', 'two_factor_enabled',
            ],
            'mail' => [
                'mail_mailer', 'mail_host', 'mail_port', 'mail_encryption',
                'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
            ],
            'backup' => [
                'backup_enabled', 'backup_frequency', 'backup_keep_count', 'backup_notify_email',
            ],
        ];

        $all = SystemSetting::all()->groupBy('group');

        // Sort groups in preferred order, then sort settings within each group
        $settings = collect($groupOrder)
            ->filter(fn($g) => $all->has($g))
            ->mapWithKeys(function ($g) use ($all, $keyOrder) {
                $order = $keyOrder[$g] ?? [];
                $sorted = $all[$g]->sortBy(function ($s) use ($order) {
                    $pos = array_search($s->key, $order);
                    return $pos !== false ? $pos : 999 + ord($s->label[0]);
                })->values();
                return [$g => $sorted];
            });

        // Append any groups not in the preferred list (future-proof)
        $all->keys()->diff($groupOrder)->each(function ($g) use ($all, &$settings) {
            $settings[$g] = $all[$g]->sortBy('label')->values();
        });

        return view('admin.settings.index', compact('settings'));
    }

public function update(Request $request)
{
    $settings = SystemSetting::all();
    $changed  = [];

    foreach ($settings as $setting) {
        if ($setting->type === 'boolean') {
            $newValue = $request->has("settings.{$setting->key}") ? '1' : '0';

        } elseif ($setting->type === 'password') {
            // Blank submission = keep existing value unchanged
            $submitted = $request->input("settings.{$setting->key}");
            if (is_null($submitted) || $submitted === '') {
                continue;
            }
            // Encrypt before storing
            $newValue = \Illuminate\Support\Facades\Crypt::encryptString($submitted);

            // Audit: record that the password was changed but not the value itself
            if ($newValue !== $setting->value) {
                $changed[$setting->key] = [
                    'label' => $setting->label,
                    'old'   => '***',
                    'new'   => '***',
                ];
            }
            $setting->update(['value' => $newValue]);
            Cache::forget("setting:{$setting->key}");
            continue; // Don't fall through to the generic update below

        } else {
            $newValue = $request->input("settings.{$setting->key}");
        }

        // Only process if a value was submitted
        if (is_null($newValue)) {
            continue;
        }

        // Track what actually changed
        if ((string) $newValue !== (string) $setting->value) {
            $changed[$setting->key] = [
                'label' => $setting->label,
                'old'   => $setting->value,
                'new'   => $newValue,
            ];
        }

        $setting->update(['value' => $newValue]);
        Cache::forget("setting:{$setting->key}");
    }

    SystemSetting::clearCache();

    if (!empty($changed)) {
        AuditLogger::settingsChanged($changed, auth()->user());
    }

    $count = count($changed);
    $message = $count > 0
        ? "Settings saved. {$count} value(s) updated."
        : 'Settings saved. No values were changed.';

    return back()->with('success', $message);
}

    public function sendTestMail(Request $request)
    {
        $recipient = $request->input('test_email', auth()->user()->email);

        // Temporarily re-apply DB mail settings so the test uses the just-saved values
        try {
            $mailer     = SystemSetting::get('mail_mailer', config('mail.default'));
            $host       = SystemSetting::get('mail_host');
            $port       = (int) SystemSetting::get('mail_port', 587);
            $username   = SystemSetting::get('mail_username');
            $password   = SystemSetting::get('mail_password');
            $encryption = SystemSetting::get('mail_encryption', 'tls');
            $fromAddr   = SystemSetting::get('mail_from_address', config('mail.from.address'));
            $fromName   = SystemSetting::get('mail_from_name',    config('mail.from.name'));

            config([
                'mail.default'               => $mailer,
                'mail.mailers.smtp.host'     => $host,
                'mail.mailers.smtp.port'     => $port,
                'mail.mailers.smtp.username' => $username ?: null,
                'mail.mailers.smtp.password' => $password ?: null,
                'mail.mailers.smtp.scheme'   => $encryption === 'ssl' ? 'smtps'
                                             : ($encryption === 'tls' ? 'smtp' : null),
                'mail.from.address'          => $fromAddr,
                'mail.from.name'             => $fromName,
            ]);

            // Purge any cached SMTP transport so it uses the new config
            app('mail.manager')->purge($mailer);

            Mail::raw(
                "This is a test email from the GOIL Budget System.\n\n"
                . "If you received this, your mail settings are working correctly.\n\n"
                . "Sent: " . now()->format('d M Y H:i:s'),
                fn($msg) => $msg
                    ->to($recipient)
                    ->subject('✅ GOIL Budget — Mail Configuration Test')
            );

            AuditLogger::record('mail_test_sent', 'admin', 'manual', [
                'subject_label' => "Test email sent to {$recipient}",
                'severity'      => 'info',
            ]);

            return back()->with('success', "Test email sent to {$recipient}. Check the inbox.");
        } catch (\Exception $e) {
            return back()->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    public function sendApproverReminders()
    {
        $sent = self::dispatchApproverReminders();

        AuditLogger::record('approver_reminders_sent', 'admin', 'manual', [
            'subject_label' => "Manual approver reminder: {$sent} notification(s) created",
            'severity'      => 'info',
        ]);

        return back()->with('success',
            $sent > 0
                ? "Reminders sent — {$sent} approver notification(s) created."
                : 'No pending budgets found requiring reminders.'
        );
    }

    /**
     * Core reminder logic shared by manual trigger and the scheduled console command.
     */
    public static function dispatchApproverReminders(): int
    {
        $stages  = ApprovalStage::ordered();
        $created = 0;

        $pending = BudgetVersion::with('department','period')
            ->whereIn('status', ['submitted','under_review'])
            ->get();

        foreach ($pending as $version) {
            // Find the first active stage
            $stage = $stages->first();
            if (!$stage) continue;

            $approvers = \App\Models\User::role($stage->role_name)
                ->where('is_active', true)
                ->get();

            foreach ($approvers as $user) {
                // De-dup: skip if a reminder was created within the last 20 hours
                $recent = BudgetNotification::where('user_id', $user->id)
                    ->where('type', 'approval_reminder')
                    ->where('notifiable_id', $version->id)
                    ->where('notifiable_type', BudgetVersion::class)
                    ->where('created_at', '>=', now()->subHours(20))
                    ->exists();

                if ($recent) continue;

                BudgetNotification::create([
                    'user_id'         => $user->id,
                    'type'            => 'approval_reminder',
                    'subject'         => "Pending approval — {$version->ownerName()} ({$version->period->name})",
                    'message'         => "{$version->ownerName()}'s budget for {$version->period->name} is waiting "
                                       . "for your review at the {$stage->name} stage. Please take action.",
                    'notifiable_id'   => $version->id,
                    'notifiable_type' => BudgetVersion::class,
                ]);

                try {
                    if (SystemSetting::get('email_notifications_enabled', false)) {
                        Mail::raw(
                            "Dear {$user->name},\n\n"
                            . "⏰  BUDGET APPROVAL REMINDER\n\n"
                            . "{$version->ownerName()}'s budget for {$version->period->name} "
                            . "is waiting for your approval at the {$stage->name} stage.\n\n"
                            . "Please log in and take action.",
                            fn($msg) => $msg
                                ->to($user->email, $user->name)
                                ->subject("⏰ Approval reminder — {$version->ownerName()}")
                        );
                    }
                } catch (\Exception) {}

                $created++;
            }
        }

        return $created;
    }
}
