<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemSettingController extends Controller
{
    public function index()
    {
        // Preferred group display order
        $groupOrder = ['general', 'budget', 'notifications', 'security', 'backup'];

        // Preferred key order within each group (keys not listed fall to the end alphabetically)
        $keyOrder = [
            'general' => [
                'app_name', 'company_name', 'currency_symbol', 'fiscal_year_start',
            ],
            'budget' => [
                'max_budget_versions', 'budget_entry_deadline_days',
                // Calculation mode first, then the settings that depend on it
                'line_item_calc_mode', 'admin_sets_rate', 'admin_sets_freq',
                'require_justification',
                'actuals_budget_check_mode',
                'actuals_approval_flow',
                'allow_revision_of_revision',
                'allow_virement_after_approval', 'virement_limit_pct',
            ],
            'notifications' => [
                'email_notifications_enabled',
                'notify_on_submission', 'notify_on_approval', 'notify_on_rejection',
                'notify_on_virement', 'notify_finance_on_virement',
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
            // Check both possible field names
            $newValue = $request->has("settings.{$setting->key}")
                ? '1'
                : '0';
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

}
