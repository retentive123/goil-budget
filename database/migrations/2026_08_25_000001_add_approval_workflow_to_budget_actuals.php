<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends budget_actuals to support a multi-stage approval workflow:
 *   draft → submitted → head_confirmed → confirmed
 *
 * Also adds audit columns for each transition, and inserts the
 * actuals_approval_flow system setting (default: simple).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend the status enum to include intermediate approval states.
        //    The final locked state is still 'confirmed' so all existing
        //    YTD / report queries continue to work without changes.
        DB::statement("
            ALTER TABLE budget_actuals
            MODIFY COLUMN status
                ENUM('draft','submitted','head_confirmed','confirmed')
                NOT NULL DEFAULT 'draft'
        ");

        // 2. Add per-transition audit columns.
        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by')->nullable()->after('approved_by');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->unsignedBigInteger('head_confirmed_by')->nullable()->after('submitted_at');
            $table->timestamp('head_confirmed_at')->nullable()->after('head_confirmed_by');

            $table->foreign('submitted_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
            $table->foreign('head_confirmed_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });

        // 3. Seed the actuals_approval_flow system setting.
        DB::table('system_settings')->insertOrIgnore([
            'group'       => 'budget',
            'key'         => 'actuals_approval_flow',
            'label'       => 'Actuals Approval Flow',
            'description' => 'Simple: anyone with "confirm actuals" locks the month directly. '
                           . 'Multi-stage: dept user submits → dept head confirms → finance gives final approval.',
            'value'       => 'multi_stage',
            'type'        => 'string',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['head_confirmed_by']);
            $table->dropColumn(['submitted_by', 'submitted_at', 'head_confirmed_by', 'head_confirmed_at']);
        });

        DB::statement("
            ALTER TABLE budget_actuals
            MODIFY COLUMN status
                ENUM('draft','confirmed')
                NOT NULL DEFAULT 'draft'
        ");

        DB::table('system_settings')->where('key', 'actuals_approval_flow')->delete();
    }
};
