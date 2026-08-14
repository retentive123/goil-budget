<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $rows = [
            [
                'key'         => 'line_item_calc_mode',
                'value'       => 'none',
                'type'        => 'string',
                'label'       => 'Budget Line Item Calculation Mode',
                'description' => 'Controls how budget amounts are computed. '
                               . '"Direct" = type amounts directly. '
                               . '"Qty × Rate" = quantity times rate gives the annual total. '
                               . '"Qty × Rate × Frequency" = adds a frequency multiplier.',
                'group'       => 'budget',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'admin_sets_rate',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Admin Controls Default Rate',
                'description' => 'When enabled, the unit rate for each account code is set by admins '
                               . 'and is read-only for budget inputters. '
                               . 'Only applies when calculation mode is Qty × Rate or Qty × Rate × Frequency.',
                'group'       => 'budget',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'admin_sets_freq',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Admin Controls Default Frequency',
                'description' => 'When enabled, the frequency multiplier for each account code is set by admins '
                               . 'and is read-only for budget inputters. '
                               . 'Only applies when calculation mode is Qty × Rate × Frequency.',
                'group'       => 'budget',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('system_settings')
              ->insertOrIgnore($row);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
          ->whereIn('key', ['line_item_calc_mode', 'admin_sets_rate', 'admin_sets_freq'])
          ->delete();
    }
};
