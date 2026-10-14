<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BudgetResetEntries extends Command
{
    protected $signature   = 'budget:reset-entries {--force : Skip confirmation prompt}';
    protected $description = 'Clear all budget entries, actuals, approvals and supplementary requests while keeping users, departments, account codes, periods and settings intact.';

    // Cleared in dependency order (children before parents).
    // Structural tables (departments, account codes, periods, users,
    // settings, approval stages) are intentionally left untouched.
    private array $tables = [
        'budget_actuals',
        'budget_line_item_custom_values',
        'line_item_approvals',
        'approval_decisions',
        'supplementary_budgets',
        'virements',
        'budget_line_items',
        'budget_versions',
        'budget_notifications',
        'submission_deadline_overrides',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=yellow;options=bold>BUDGET ENTRIES RESET</>');
        $this->newLine();
        $this->line('  The following tables will be <fg=red>truncated</>:');
        $this->newLine();

        foreach ($this->tables as $table) {
            $count = DB::table($table)->count();
            $this->line(sprintf('    <fg=gray>%-45s</> <fg=red>%s row(s)</>', $table, number_format($count)));
        }

        $this->newLine();
        $this->line('  <fg=green>Kept intact:</>  users · departments · account codes · budget periods · approval stages · settings');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('  Are you sure you want to delete all budget entries? This cannot be undone.', false)) {
                $this->line('  <fg=yellow>Aborted.</> No changes made.');
                return Command::SUCCESS;
            }

            $confirm = $this->ask('  Type  RESET  to confirm');
            if (trim($confirm) !== 'RESET') {
                $this->line('  <fg=yellow>Aborted.</> No changes made.');
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->line('  Clearing tables…');
        $this->newLine();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            foreach ($this->tables as $table) {
                DB::table($table)->delete();
                $this->line("    <fg=green>✓</> {$table}");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        Cache::flush();

        $this->newLine();
        $this->line('  <fg=green;options=bold>Done.</> All budget entries cleared.');
        $this->line('  Users, departments, account codes, periods and settings are untouched.');
        $this->newLine();

        return Command::SUCCESS;
    }
}
