<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Virement cross-type settings ──────────────────────────────────
        $settings = [
            [
                'key'         => 'allow_cross_type_virement',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Allow Cross-Type Virements',
                'description' => 'Allow virements between different budget types (e.g. from expense to capital). Off by default.',
                'group'       => 'budget',
            ],
            [
                'key'         => 'cross_type_virement_limit_pct',
                'value'       => '5',
                'type'        => 'integer',
                'label'       => 'Cross-Type Virement Limit (%)',
                'description' => 'Maximum % of an account code budget that can be vired out to a different budget type. Applied separately from the same-type virement limit.',
                'group'       => 'budget',
            ],
        ];

        foreach ($settings as $s) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $s['key']],
                array_merge($s, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // ── 2. Multiple actuals per month ─────────────────────────────────────
        // Drop the unique constraint that enforced one row per (line_item, month, year)
        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->dropUnique('ba_line_month_year_unique');
            $table->date('entry_date')->nullable()->after('year');
        });
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['allow_cross_type_virement', 'cross_type_virement_limit_pct'])
            ->delete();

        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->dropColumn('entry_date');
            $table->unique(['budget_line_item_id', 'month', 'year'], 'ba_line_month_year_unique');
        });
    }
};
