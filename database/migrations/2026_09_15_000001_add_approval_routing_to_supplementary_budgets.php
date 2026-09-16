<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplementary_budgets', function (Blueprint $table) {
            // Dept-head approval step (for dept_head_final mode)
            $table->string('dept_head_status')->nullable()->after('status'); // pending|approved|rejected
            $table->unsignedBigInteger('dept_head_by')->nullable()->after('dept_head_status');
            $table->timestamp('dept_head_at')->nullable()->after('dept_head_by');
            $table->text('dept_head_notes')->nullable()->after('dept_head_at');

            // Full-stages mode: tracks which stage the request is currently at
            $table->unsignedInteger('current_stage_order')->nullable()->after('dept_head_notes');

            $table->foreign('dept_head_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplementary_budgets', function (Blueprint $table) {
            $table->dropForeign(['dept_head_by']);
            $table->dropColumn([
                'dept_head_status', 'dept_head_by', 'dept_head_at',
                'dept_head_notes', 'current_stage_order',
            ]);
        });
    }
};
