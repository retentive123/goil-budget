<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE budget_line_items MODIFY COLUMN line_type ENUM('revenue','expense','capex','asset','liability') NOT NULL DEFAULT 'expense'");
    }

    public function down(): void
    {
        DB::statement("UPDATE budget_line_items SET line_type = 'expense' WHERE line_type NOT IN ('revenue','expense')");
        DB::statement("ALTER TABLE budget_line_items MODIFY COLUMN line_type ENUM('revenue','expense') NOT NULL DEFAULT 'expense'");
    }
};
