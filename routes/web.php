<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\AccountCodeController;
use App\Http\Controllers\Admin\AccountCategoryController;
use App\Http\Controllers\Admin\AccountSubCategoryController;
use App\Http\Controllers\Admin\IncomeStatementConfigController;
use App\Http\Controllers\Admin\BalanceSheetConfigController;
use App\Http\Controllers\Admin\CapexConfigController;
use App\Http\Controllers\Admin\ExpumpTemplateController;
use App\Http\Controllers\Admin\BudgetPeriodController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Budget\BudgetEntryController;
use App\Http\Controllers\Budget\BudgetSubmissionController;
use App\Http\Controllers\Approval\ApprovalController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Budget\VirementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Actuals\ActualController;
use App\Http\Controllers\Admin\ApprovalStageController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Supplementary\SupplementaryBudgetController;
use App\Http\Controllers\Admin\DeadlineOverrideController;
use App\Http\Controllers\Budget\AllBudgetsController;
use App\Http\Controllers\DocsController;


// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/',      [LoginController::class, 'showLoginForm']);
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login',[LoginController::class, 'login']);
    Route::get('/2fa',           [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa/verify',   [TwoFactorController::class, 'verify'])->name('2fa.verify')->middleware('throttle:6,1');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
    Route::get('/password/change',  [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/2fa/setup',    [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/enable',  [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])
        ->name('2fa.disable')
        ->middleware('permission:disable two factor');


    // -------------------------------------------------------
    // Admin routes — all inside prefix('admin')->name('admin.')
    // -------------------------------------------------------
    Route::middleware('permission:manage users')->prefix('admin')->name('admin.')->group(function () {

        // Users
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/assign-role',    [UserController::class, 'assignRole'])->name('users.assign-role');

        // Departments
        Route::resource('departments', DepartmentController::class);
        Route::get('departments/{department}/account-codes',  [DepartmentController::class, 'accountCodes'])->name('departments.account-codes');
        Route::post('departments/{department}/account-codes', [DepartmentController::class, 'syncAccountCodes'])->name('departments.sync-account-codes');

        // P&L layout configuration
        Route::resource('income-statement-configs', IncomeStatementConfigController::class)->except('show');
        Route::post('income-statement-configs/{incomeStatementConfig}/activate',   [IncomeStatementConfigController::class, 'activate'])->name('income-statement-configs.activate');
        Route::post('income-statement-configs/{incomeStatementConfig}/deactivate', [IncomeStatementConfigController::class, 'deactivate'])->name('income-statement-configs.deactivate');

        // Balance sheet layout configuration
        Route::resource('balance-sheet-configs', BalanceSheetConfigController::class)->except('show');
        Route::post('balance-sheet-configs/{balanceSheetConfig}/activate',   [BalanceSheetConfigController::class, 'activate'])->name('balance-sheet-configs.activate');
        Route::post('balance-sheet-configs/{balanceSheetConfig}/deactivate', [BalanceSheetConfigController::class, 'deactivate'])->name('balance-sheet-configs.deactivate');

        // CapEx layout configuration
        Route::resource('capex-configs', CapexConfigController::class)->except('show');
        Route::post('capex-configs/{capexConfig}/activate',   [CapexConfigController::class, 'activate'])->name('capex-configs.activate');
        Route::post('capex-configs/{capexConfig}/deactivate', [CapexConfigController::class, 'deactivate'])->name('capex-configs.deactivate');

        // Ex-pump price templates
        Route::resource('expump-templates', ExpumpTemplateController::class);
        Route::post('expump-templates/{expumpTemplate}/activate',   [ExpumpTemplateController::class, 'activate'])->name('expump-templates.activate');
        Route::post('expump-templates/{expumpTemplate}/deactivate', [ExpumpTemplateController::class, 'deactivate'])->name('expump-templates.deactivate');

        // Account sub-categories, categories & codes
        Route::resource('account-sub-categories', AccountSubCategoryController::class);
        Route::delete('account-categories', [AccountCategoryController::class, 'bulkDestroy'])->name('account-categories.bulk-destroy');
        Route::post('account-categories/bulk-assign-sub-category', [AccountCategoryController::class, 'bulkAssignSubCategory'])->name('account-categories.bulk-assign-sub-category');
        Route::resource('account-categories', AccountCategoryController::class);
        Route::resource('account-codes', AccountCodeController::class);
        Route::delete('account-codes', [AccountCodeController::class, 'bulkDestroy'])->name('account-codes.bulk-destroy');

        // Budget periods
        Route::resource('budget-periods', BudgetPeriodController::class);
        Route::patch('budget-periods/{budgetPeriod}/open',  [BudgetPeriodController::class, 'open'])->name('budget-periods.open');
        Route::patch('budget-periods/{budgetPeriod}/close', [BudgetPeriodController::class, 'close'])->name('budget-periods.close');

        // Audit log
        Route::middleware('permission:view audit log')->group(function () {
            Route::get('audit-log',         [AuditLogController::class, 'index'])->name('audit-log.index');
            Route::get('audit-log/export',  [AuditLogController::class, 'export'])->name('audit-log.export');
            Route::get('audit-log/{id}',    [AuditLogController::class, 'show'])->name('audit-log.show');
        });

        // System settings
        Route::middleware('permission:manage system settings')->group(function () {
            Route::get('settings',  [SystemSettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SystemSettingController::class, 'update'])->name('settings.update');
        });


        Route::resource('roles', RoleController::class);
        Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');
        Route::get('permissions',               [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions',              [PermissionController::class, 'store'])->name('permissions.store');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');



        Route::resource('approval-stages', ApprovalStageController::class);
        Route::post('approval-stages/{approvalStage}/move-up',   [ApprovalStageController::class, 'moveUp'])->name('approval-stages.move-up');
        Route::post('approval-stages/{approvalStage}/move-down', [ApprovalStageController::class, 'moveDown'])->name('approval-stages.move-down');
        Route::post('approval-stages/reorder',                   [ApprovalStageController::class, 'reorder'])->name('approval-stages.reorder');


        Route::middleware('permission:manage system settings')->group(function () {
            Route::get('backups',             [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups',            [BackupController::class, 'store'])->name('backups.store');
            Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
            Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
        });

        Route::middleware('permission:grant deadline override')->group(function () {
            Route::get('deadline-overrides',  [DeadlineOverrideController::class, 'index'])->name('deadline-overrides.index');
            Route::post('deadline-overrides', [DeadlineOverrideController::class, 'store'])->name('deadline-overrides.store');
            Route::post('deadline-overrides/{override}/revoke', [DeadlineOverrideController::class, 'revoke'])->name('deadline-overrides.revoke');
        });




        });

        // All Budgets — for finance, admin, GCEO, board
        Route::middleware(['auth', 'permission:view all budgets'])
            ->prefix('budgets')
            ->name('budgets.')
            ->group(function () {
                Route::get('/',                          [AllBudgetsController::class, 'index'])->name('index');
                Route::get('/export',                    [AllBudgetsController::class, 'export'])->name('export');
                Route::get('/{budgetVersion}/pnl',       [AllBudgetsController::class, 'showPnl'])->name('show-pnl');
                Route::get('/{budgetVersion}',           [AllBudgetsController::class, 'show'])->name('show');
                Route::get('/department/{department}',   [AllBudgetsController::class, 'department'])->name('department');
            });

    // -------------------------------------------------------
    // Budget entry routes
    // -------------------------------------------------------
    Route::middleware('permission:create budget')->prefix('budget')->name('budget.')->group(function () {
        Route::get('/',                            [BudgetEntryController::class, 'index'])->name('index');
        Route::post('/start',                      [BudgetEntryController::class, 'start'])->name('start');
        Route::get('/{budgetVersion}/pnl',         [BudgetEntryController::class, 'showPnl'])->name('show-pnl');
        Route::get('/{budgetVersion}',             [BudgetEntryController::class, 'show'])->name('show');
        Route::post('/{budgetVersion}/save',       [BudgetEntryController::class, 'save'])->name('save');
        Route::post('/{budgetVersion}/submit',     [BudgetSubmissionController::class, 'submit'])->name('submit');
        Route::get('/{budgetVersion}/confirm',     [BudgetSubmissionController::class, 'confirm'])->name('confirm');
    });



    // Supplementary budget routes
    Route::prefix('supplementary')->name('supplementary.')->group(function () {

        Route::middleware('permission:request supplementary budget|approve supplementary budget')->group(function () {
            Route::get('/', [SupplementaryBudgetController::class, 'index'])->name('index');
        });

        Route::middleware('permission:request supplementary budget')->group(function () {
            Route::get('/create',  [SupplementaryBudgetController::class, 'create'])->name('create');
            Route::post('/',       [SupplementaryBudgetController::class, 'store'])->name('store');
        });

        Route::middleware('permission:approve supplementary budget')->group(function () {
            Route::get('/pending',                         [SupplementaryBudgetController::class, 'pending'])->name('pending');
            Route::post('/{supplementary}/approve',        [SupplementaryBudgetController::class, 'approve'])->name('approve');
            Route::post('/{supplementary}/reject',         [SupplementaryBudgetController::class, 'reject'])->name('reject');
            Route::post('/batch/{batchId}/approve',        [SupplementaryBudgetController::class, 'approveBatch'])->name('approve-batch');
            Route::post('/batch/{batchId}/reject',         [SupplementaryBudgetController::class, 'rejectBatch'])->name('reject-batch');
            Route::delete('/{supplementary}',              [SupplementaryBudgetController::class, 'destroy'])->name('destroy');
            Route::delete('/batch/{batchId}',              [SupplementaryBudgetController::class, 'destroyBatch'])->name('destroy-batch');
        });

        // Wildcard show route must come last so it doesn't swallow /pending and /create
        Route::middleware('permission:request supplementary budget|approve supplementary budget')->group(function () {
            Route::get('/{supplementary}', [SupplementaryBudgetController::class, 'show'])->name('show');
        });
    });

    // -------------------------------------------------------
    // Approval routes
    // -------------------------------------------------------
    Route::middleware('permission:approve budget')->prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/',                        [ApprovalController::class, 'index'])->name('index');
        Route::get('/{budgetVersion}/pnl',     [ApprovalController::class, 'showPnl'])->name('show-pnl');
        Route::get('/{budgetVersion}',         [ApprovalController::class, 'show'])->name('show');
        Route::post('/{budgetVersion}/decide', [ApprovalController::class, 'decide'])->name('decide');
        Route::get('/{budgetVersion}/history', [ApprovalController::class, 'history'])->name('history');
    });

    // -------------------------------------------------------
    // Report routes
    // -------------------------------------------------------
 Route::middleware('permission:view reports')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/',                [ReportController::class, 'index'])->name('index');
    Route::get('/executive',       [ReportController::class, 'executive'])->name('executive');
    Route::get('/department',      [ReportController::class, 'department'])->name('department');
    Route::get('/code-explorer',   [ReportController::class, 'codeExplorer'])->name('code-explorer');
    Route::get('/yoy',             [ReportController::class, 'yoy'])->name('yoy');
    Route::get('/dept-comparison', [ReportController::class, 'deptComparison'])->name('dept-comparison');
    Route::get('/variance',        [ReportController::class, 'variance'])->name('variance');
    Route::get('/utilisation',     [ReportController::class, 'utilisation'])->name('utilisation');
    Route::get('/virement',        [ReportController::class, 'virement'])->name('virement');
    Route::get('/flexed',          [ReportController::class, 'flexed'])->name('flexed');
    Route::get('/approved',        [ReportController::class, 'approved'])->name('approved');
    Route::get('/financial',       [ReportController::class, 'financialStatement'])->name('financial');
    Route::get('/capex',           [ReportController::class, 'capex'])->name('capex');

    Route::middleware('permission:export reports')->group(function () {
        Route::get('/export/approved',    [ReportController::class, 'exportApproved'])->name('export.approved');
        Route::get('/export/variance',    [ReportController::class, 'exportVariance'])->name('export.variance');
        Route::get('/export/utilisation', [ReportController::class, 'exportUtilisation'])->name('export.utilisation');
        Route::get('/export/virement',    [ReportController::class, 'exportVirement'])->name('export.virement');
        Route::get('/export/pdf/{type}',  [ReportController::class, 'exportPdf'])->name('export.pdf');

        Route::get('code-explorer/export', [ReportController::class, 'codeExplorerExport'])
        ->name('code-explorer.export');

    });

});

    // -------------------------------------------------------
    // Virement routes
    // -------------------------------------------------------
    Route::prefix('virements')->name('virements.')->group(function () {
        // Static routes must come before /{virement} wildcard
        Route::middleware('permission:request virement')->group(function () {
            Route::get('/',       [VirementController::class, 'index'])->name('index');
            Route::get('/create', [VirementController::class, 'create'])->name('create');
            Route::post('/',      [VirementController::class, 'store'])->name('store');
        });

        Route::middleware('permission:approve virement')->group(function () {
            Route::get('/pending',             [VirementController::class, 'pending'])->name('pending');
            Route::post('/{virement}/approve', [VirementController::class, 'approve'])->name('approve');
            Route::post('/{virement}/reject',  [VirementController::class, 'reject'])->name('reject');
        });

        // Wildcard last so it doesn't swallow static routes above
        Route::middleware('permission:request virement')->group(function () {
            Route::get('/{virement}', [VirementController::class, 'show'])->name('show');
        });
    });

    // -------------------------------------------------------
    // Notification routes
    // -------------------------------------------------------
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                     [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all',            [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('/{notification}',    [NotificationController::class, 'destroy'])->name('destroy');
    });


    // Actuals routes
    Route::prefix('actuals')->name('actuals.')->group(function () {
        Route::middleware('permission:view all budgets|create budget')->group(function () {
            Route::get('/',         [ActualController::class, 'index'])->name('index');
            Route::get('/entry',    [ActualController::class, 'entry'])->name('entry');
            Route::get('/overview', [ActualController::class, 'overview'])->name('overview');
        });

        Route::middleware('permission:approve budget|create budget')->group(function () {
            Route::post('/store',    [ActualController::class, 'store'])->name('store');
            Route::post('/autosave', [ActualController::class, 'autosave'])->name('autosave');
            Route::post('/confirm',  [ActualController::class, 'confirm'])->name('confirm');
        });
    });



// Import / Export routes
Route::prefix('import-export')->name('ie.')->group(function () {

    // Downloads are available to anyone with budget access (read-only)
    Route::get('budget/{budgetVersion}/download',
        [ImportExportController::class, 'downloadBudgetTemplate'])->name('budget.download');
    Route::get('budget/{budgetVersion}/download-pnl',
        [ImportExportController::class, 'downloadPnlBudgetTemplate'])->name('budget.download-pnl');
    Route::get('budget/{budgetVersion}/export',
        [ImportExportController::class, 'exportBudget'])->name('budget.export');
    Route::get('budget/{budgetVersion}/export-pnl',
        [ImportExportController::class, 'exportPnlBudget'])->name('budget.export-pnl');
    Route::get('actuals/download',
        [ImportExportController::class, 'downloadActualsTemplate'])->name('actuals.download');
    Route::get('categories/download',
        [ImportExportController::class, 'downloadCategoryTemplate'])->name('categories.download');
    Route::get('codes/download',
        [ImportExportController::class, 'downloadCodeTemplate'])->name('codes.download');

    // Budget & actuals uploads require budget creation permission
    Route::middleware('permission:create budget|approve budget')->group(function () {
        Route::post('budget/{budgetVersion}/upload',
            [ImportExportController::class, 'uploadBudget'])->name('budget.upload');
        Route::post('actuals/upload',
            [ImportExportController::class, 'uploadActuals'])->name('actuals.upload');
    });

    // Master-data uploads (categories, codes) are admin-only
    Route::middleware('permission:manage categories|manage users')->group(function () {
        Route::post('categories/upload',
            [ImportExportController::class, 'uploadCategories'])->name('categories.upload');
        Route::post('codes/upload',
            [ImportExportController::class, 'uploadCodes'])->name('codes.upload');
    });
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin/maintenance')->name('admin.maintenance.')->group(function () {
            Route::get('/',         [MaintenanceController::class, 'index'])->name('index');
            Route::post('/enable',  [MaintenanceController::class, 'enable'])->name('enable');
            Route::post('/disable', [MaintenanceController::class, 'disable'])->name('disable');
        });
        //comment

});
