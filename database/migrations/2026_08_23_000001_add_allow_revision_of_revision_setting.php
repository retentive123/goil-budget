<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'allow_revision_of_revision'],
            [
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Allow Re-Revision of Approved Revision',
                'description' => 'Allow departments to revise a budget that is itself an already-approved revision. When disabled, only the original approved budget can be revised.',
                'group'       => 'budget',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'allow_revision_of_revision')->delete();
    }
};
