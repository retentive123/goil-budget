<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\UniversalAuditObserver;

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
    }
}
