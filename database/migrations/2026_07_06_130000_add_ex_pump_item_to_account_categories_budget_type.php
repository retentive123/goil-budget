<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE account_categories MODIFY COLUMN budget_type ENUM('revenue','expense','both','capital_expenditure','assets','liabilities','ex_pump_item') NOT NULL DEFAULT 'expense'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE account_categories MODIFY COLUMN budget_type ENUM('revenue','expense','both','capital_expenditure','assets','liabilities') NOT NULL DEFAULT 'expense'");
    }
};
