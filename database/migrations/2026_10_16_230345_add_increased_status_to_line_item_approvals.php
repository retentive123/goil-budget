<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `line_item_approvals` MODIFY COLUMN `status` ENUM('approved','rejected','reduced','increased') NOT NULL");

        DB::table('system_settings')->where('key', 'approval_allow_restore')->update([
            'label'       => 'Allow approvers to adjust line item amounts',
            'description' => 'When enabled, approvers can increase a line item above its original budget, or restore a previously-reduced amount back to the original.',
        ]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `line_item_approvals` MODIFY COLUMN `status` ENUM('approved','rejected','reduced') NOT NULL");

        DB::table('system_settings')->where('key', 'approval_allow_restore')->update([
            'label'       => 'Allow approvers to restore reduced amounts',
            'description' => 'When enabled, a later approver can increase a previously-reduced line item up to the original budget amount.',
        ]);
    }
};
