<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add entry_mode to budget_periods
        Schema::table('budget_periods', function (Blueprint $table) {
            $table->string('entry_mode', 10)->default('quarterly')->after('created_by');
        });

        // 2. Add m1–m12 columns to budget_line_items
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->decimal('m1_amount',  15, 2)->default(0)->after('q4_amount');
            $table->decimal('m2_amount',  15, 2)->default(0)->after('m1_amount');
            $table->decimal('m3_amount',  15, 2)->default(0)->after('m2_amount');
            $table->decimal('m4_amount',  15, 2)->default(0)->after('m3_amount');
            $table->decimal('m5_amount',  15, 2)->default(0)->after('m4_amount');
            $table->decimal('m6_amount',  15, 2)->default(0)->after('m5_amount');
            $table->decimal('m7_amount',  15, 2)->default(0)->after('m6_amount');
            $table->decimal('m8_amount',  15, 2)->default(0)->after('m7_amount');
            $table->decimal('m9_amount',  15, 2)->default(0)->after('m8_amount');
            $table->decimal('m10_amount', 15, 2)->default(0)->after('m9_amount');
            $table->decimal('m11_amount', 15, 2)->default(0)->after('m10_amount');
            $table->decimal('m12_amount', 15, 2)->default(0)->after('m11_amount');
        });

        // 3. Spread existing quarterly amounts equally into months (÷3, remainder in last month)
        DB::statement("
            UPDATE budget_line_items SET
                m1_amount  = ROUND(q1_amount / 3, 2),
                m2_amount  = ROUND(q1_amount / 3, 2),
                m3_amount  = ROUND(q1_amount - ROUND(q1_amount / 3, 2) * 2, 2),
                m4_amount  = ROUND(q2_amount / 3, 2),
                m5_amount  = ROUND(q2_amount / 3, 2),
                m6_amount  = ROUND(q2_amount - ROUND(q2_amount / 3, 2) * 2, 2),
                m7_amount  = ROUND(q3_amount / 3, 2),
                m8_amount  = ROUND(q3_amount / 3, 2),
                m9_amount  = ROUND(q3_amount - ROUND(q3_amount / 3, 2) * 2, 2),
                m10_amount = ROUND(q4_amount / 3, 2),
                m11_amount = ROUND(q4_amount / 3, 2),
                m12_amount = ROUND(q4_amount - ROUND(q4_amount / 3, 2) * 2, 2)
        ");

        // 4. Drop the generated total_amount column (it depends on q1–q4)
        DB::statement('ALTER TABLE budget_line_items DROP COLUMN total_amount');

        // 5. Drop the quarterly columns
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->dropColumn(['q1_amount', 'q2_amount', 'q3_amount', 'q4_amount']);
        });

        // 6. Recreate total_amount as a stored generated column summing all 12 months
        DB::statement('
            ALTER TABLE budget_line_items
            ADD COLUMN total_amount DECIMAL(15,2) GENERATED ALWAYS AS (
                m1_amount  + m2_amount  + m3_amount  + m4_amount  +
                m5_amount  + m6_amount  + m7_amount  + m8_amount  +
                m9_amount  + m10_amount + m11_amount + m12_amount
            ) STORED
        ');
    }

    public function down(): void
    {
        // Reverse: add back quarterly, aggregate months into quarters, drop monthly
        Schema::table('budget_periods', function (Blueprint $table) {
            $table->dropColumn('entry_mode');
        });

        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->decimal('q1_amount', 15, 2)->default(0);
            $table->decimal('q2_amount', 15, 2)->default(0);
            $table->decimal('q3_amount', 15, 2)->default(0);
            $table->decimal('q4_amount', 15, 2)->default(0);
        });

        DB::statement("
            UPDATE budget_line_items SET
                q1_amount = m1_amount + m2_amount + m3_amount,
                q2_amount = m4_amount + m5_amount + m6_amount,
                q3_amount = m7_amount + m8_amount + m9_amount,
                q4_amount = m10_amount + m11_amount + m12_amount
        ");

        DB::statement('ALTER TABLE budget_line_items DROP COLUMN total_amount');

        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->dropColumn([
                'm1_amount','m2_amount','m3_amount','m4_amount',
                'm5_amount','m6_amount','m7_amount','m8_amount',
                'm9_amount','m10_amount','m11_amount','m12_amount',
            ]);
        });

        DB::statement('
            ALTER TABLE budget_line_items
            ADD COLUMN total_amount DECIMAL(15,2) GENERATED ALWAYS AS (
                q1_amount + q2_amount + q3_amount + q4_amount
            ) STORED
        ');
    }
};
