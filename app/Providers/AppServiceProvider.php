<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Observers\UniversalAuditObserver;
use App\Models\Zone;

use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;
use App\Models\Virement;
use App\Models\SupplementaryBudget;
use App\Models\BudgetActual;
use App\Models\User;
use App\Models\Department;
use App\Models\AccountCategory;
use App\Models\AccountCode;
use App\Models\SystemSetting;
use App\Models\SubmissionDeadlineOverride;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $models = [
            BudgetVersion::class,
            BudgetLineItem::class,
            BudgetPeriod::class,
            Virement::class,
            SupplementaryBudget::class,
            BudgetActual::class,
            User::class,
            Department::class,
            AccountCategory::class,
            AccountCode::class,
            SystemSetting::class,
            SubmissionDeadlineOverride::class,
            Role::class,
        ];

        foreach ($models as $model) {
            $model::observe(UniversalAuditObserver::class);
        }

        // Bind 'serviceStation' route parameter to the Department model
        Route::bind('serviceStation', fn($value) => Department::serviceStations()->findOrFail($value));

        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // ── Apply mail settings from the database (overrides .env) ────────────
        // Wrapped in try/catch so the app still boots if the DB isn't available
        // (e.g. during migrations or first-run setup).
        try {
            if (Schema::hasTable('system_settings') &&
                SystemSetting::where('group', 'mail')->exists()) {

                $mailer     = SystemSetting::get('mail_mailer',       'log');
                $host       = SystemSetting::get('mail_host',         config('mail.mailers.smtp.host'));
                $port       = (int) SystemSetting::get('mail_port',   config('mail.mailers.smtp.port', 587));
                $username   = SystemSetting::get('mail_username',     config('mail.mailers.smtp.username'));
                $password   = SystemSetting::get('mail_password');   // already decrypted by model
                $encryption = SystemSetting::get('mail_encryption',   'tls');
                $fromAddr   = SystemSetting::get('mail_from_address', config('mail.from.address'));
                $fromName   = SystemSetting::get('mail_from_name',    config('mail.from.name'));

                config([
                    'mail.default'                  => $mailer,
                    'mail.mailers.smtp.host'        => $host,
                    'mail.mailers.smtp.port'        => $port,
                    'mail.mailers.smtp.username'    => $username ?: null,
                    'mail.mailers.smtp.password'    => $password ?: null,
                    'mail.mailers.smtp.scheme'      => $encryption === 'ssl' ? 'smtps'
                                                    : ($encryption === 'tls' ? 'smtp' : null),
                    'mail.from.address'             => $fromAddr,
                    'mail.from.name'                => $fromName,
                ]);
            }
        } catch (\Exception) {
            // DB unavailable — fall back to .env values silently
        }
    }
}
