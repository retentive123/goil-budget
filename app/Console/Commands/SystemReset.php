<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SystemReset extends Command
{
    protected $signature   = 'system:reset {--force : Skip confirmation prompt}';
    protected $description = 'Clear all operational data (budgets, departments, account codes, actuals, etc.) while keeping users, roles, and system settings.';

    // ── Tables cleared in dependency order ────────────────────────────────────
    private array $tables = [
        // Budget operational data (deepest first)
        'budget_actuals',
        'line_item_approvals',
        'approval_decisions',
        'supplementary_budgets',
        'budget_line_items',
        'budget_versions',
        'submission_deadline_overrides',
        'virements',

        // Period-rate configuration
        'budget_period_code_rates',
        'budget_period_category_rates',
        'budget_period_settings',
        'budget_periods',

        // Notifications & audit
        'budget_notifications',
        'system_audit_logs',
        'system_backups',

        // ExPump templates
        'expump_values',
        'expump_templates',

        // Subsidiary data
        'subsidiary_account_codes',
        'subsidiaries',
        'subsidiary_categories',

        // Account structure
        'department_account_codes',
        'account_codes',
        'account_categories',
        'account_sub_categories',

        // Departments & zones
        'departments',
        'zones',

        // Approval pipeline
        'approval_stages',

        // P&L / Balance sheet / CAPEX layouts
        'income_statement_lines',
        'income_statement_configs',
        'balance_sheet_lines',
        'balance_sheet_configs',
        'capex_lines',
        'capex_configs',

        // Activity log (Spatie)
        'activity_log',

        // Laravel notifications
        'notifications',
    ];

    // ── Tables deliberately preserved ─────────────────────────────────────────
    private array $preserved = [
        'users',
        'password_reset_tokens',
        'personal_access_tokens',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'system_settings',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=red;options=bold>GOIL Budget Management System — Data Reset</>');
        $this->newLine();

        if (! $this->option('force')) {
            $this->warn('  This will permanently delete ALL operational data:');
            $this->line('    • Budget periods, versions, and line items');
            $this->line('    • Departments, account codes, and categories');
            $this->line('    • Actuals, virements, and supplementary budgets');
            $this->line('    • Approval stages and decisions');
            $this->line('    • P&L / Balance Sheet / CAPEX layouts');
            $this->line('    • Notifications and audit logs');
            $this->newLine();
            $this->info('  Preserved: users, roles, permissions, system settings');
            $this->newLine();

            if (! $this->confirm('  Are you sure you want to reset all system data?', false)) {
                $this->line('  Cancelled — no data was changed.');
                return self::SUCCESS;
            }

            $this->newLine();
            if (! $this->confirm('  <fg=red>Final confirmation — this cannot be undone. Proceed?</>', false)) {
                $this->line('  Cancelled — no data was changed.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('  Clearing data…');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $bar = $this->output->createProgressBar(count($this->tables));
        $bar->setFormat('  %current%/%max% [%bar%] %message%');
        $bar->start();

        $skipped = [];

        foreach ($this->tables as $table) {
            $bar->setMessage($table);
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            } else {
                $skipped[] = $table;
            }
            $bar->advance();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $bar->setMessage('done');
        $bar->finish();

        $this->newLine(2);

        // Clear application cache
        Cache::flush();
        $this->line('  Cache flushed.');

        if (! empty($skipped)) {
            $this->newLine();
            $this->warn('  The following tables were not found (skipped):');
            foreach ($skipped as $t) {
                $this->line("    • {$t}");
            }
        }

        $this->newLine();
        $this->info('  ✓ System reset complete.');
        $this->newLine();
        $this->line('  You can now load: departments → account categories → account codes → approval stages');
        $this->line('  then open the first budget period.');
        $this->newLine();

        return self::SUCCESS;
    }
}
