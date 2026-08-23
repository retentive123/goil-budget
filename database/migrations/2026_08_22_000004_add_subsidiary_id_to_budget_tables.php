<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds subsidiary support to the three tables that currently hard-code department_id.
 *
 * budget_versions & budget_actuals:
 *   - department_id made nullable (existing rows keep their value)
 *   - subsidiary_id added as nullable FK
 *   - app-level validation ensures exactly one is set per row
 *
 * budget_versions unique constraint:
 *   The original 'bv_period_dept_version_unique' still covers dept rows.
 *   A new 'bv_period_sub_version_unique' covers subsidiary rows.
 *
 * users:
 *   - subsidiary_id added as nullable FK (mirrors the existing nullable department_id)
 */
return new class extends Migration {
    public function up(): void
    {
        // ── budget_versions ───────────────────────────────────────────────
        Schema::table('budget_versions', function (Blueprint $table) {
            // Drop the NOT NULL + FK on department_id so subsidiaries can have null
            $table->dropForeign(['department_id']);
            $table->unsignedBigInteger('department_id')->nullable()->change();
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();

            $table->foreignId('subsidiary_id')
                  ->nullable()
                  ->after('department_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Unique constraint for subsidiary rows
            $table->unique(
                ['budget_period_id', 'subsidiary_id', 'version_number'],
                'bv_period_sub_version_unique'
            );
        });

        // ── budget_actuals ────────────────────────────────────────────────
        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->unsignedBigInteger('department_id')->nullable()->change();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();

            $table->foreignId('subsidiary_id')
                  ->nullable()
                  ->after('department_id')
                  ->constrained()
                  ->nullOnDelete();
        });

        // ── users ─────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subsidiary_id')
                  ->nullable()
                  ->after('department_id')
                  ->constrained()
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_versions', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
            $table->dropUnique('bv_period_sub_version_unique');
            $table->dropColumn('subsidiary_id');
            // Restore NOT NULL — only safe if no null rows exist
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
        });

        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
            $table->dropColumn('subsidiary_id');
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
            $table->dropColumn('subsidiary_id');
        });
    }
};
