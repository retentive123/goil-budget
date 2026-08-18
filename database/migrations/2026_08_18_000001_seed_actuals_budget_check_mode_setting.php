<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            'key'         => 'actuals_budget_check_mode',
            'value'       => 'annual',
            'type'        => 'string',
            'label'       => 'Actuals Budget Check Mode',
            'description' => 'Annual (flexible): only blocks entry if the running YTD total '
                           . 'would exceed the full annual budget — individual months may exceed '
                           . 'their monthly allocation as long as the year total is not breached. '
                           . 'Monthly (strict): each month\'s actual must not exceed that month\'s '
                           . 'specific budgeted amount.',
            'group'       => 'budget',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'actuals_budget_check_mode')
            ->delete();
    }
};
