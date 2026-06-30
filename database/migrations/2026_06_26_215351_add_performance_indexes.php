<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Budget versions — most queried combination
        Schema::table('budget_versions', function (Blueprint $table) {
            $table->index(['budget_period_id','department_id','status'], 'bv_period_dept_status');
            $table->index(['status','budget_period_id'], 'bv_status_period');
        });

        // Budget line items
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->index(['budget_version_id','account_code_id'], 'bli_version_code');
        });

        // Budget actuals — frequently filtered
        Schema::table('budget_actuals', function (Blueprint $table) {
            $table->index(['budget_period_id','department_id','status'], 'ba_period_dept_status');
            $table->index(['budget_line_item_id','month','year'], 'ba_item_month_year');
            $table->index(['department_id','month','year'], 'ba_dept_month_year');
        });

        // Approval decisions
        Schema::table('approval_decisions', function (Blueprint $table) {
            $table->index(['budget_version_id','decision'], 'ad_version_decision');
        });

        // System audit logs
        Schema::table('system_audit_logs', function (Blueprint $table) {
            $table->index(['user_id','created_at'], 'sal_user_created');
            $table->index(['module','severity'], 'sal_module_severity');
        });

        // Virements
        Schema::table('virements', function (Blueprint $table) {
            $table->index(['department_id','status'], 'vir_dept_status');
            $table->index(['budget_period_id','status'], 'vir_period_status');
        });

        // Budget notifications
        Schema::table('budget_notifications', function (Blueprint $table) {
            $table->index(['user_id','read_at'], 'bn_user_read');
        });
    }

    public function down(): void {
        Schema::table('budget_versions',      fn($t) => $t->dropIndex('bv_period_dept_status'));
        Schema::table('budget_line_items',    fn($t) => $t->dropIndex('bli_version_code'));
        Schema::table('budget_actuals',       fn($t) => $t->dropIndex('ba_period_dept_status'));
        Schema::table('approval_decisions',   fn($t) => $t->dropIndex('ad_version_decision'));
        Schema::table('system_audit_logs',    fn($t) => $t->dropIndex('sal_user_created'));
        Schema::table('virements',            fn($t) => $t->dropIndex('vir_dept_status'));
        Schema::table('budget_notifications', fn($t) => $t->dropIndex('bn_user_read'));
    }
};
