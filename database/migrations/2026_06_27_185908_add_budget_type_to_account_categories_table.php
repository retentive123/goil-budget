<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->enum('budget_type', ['revenue','expense','both'])
                  ->default('expense')
                  ->after('is_active');
        });

        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->enum('line_type', ['revenue','expense'])
                  ->default('expense')
                  ->after('justification');
        });
    }

    public function down(): void {
        Schema::table('account_categories', fn($t) => $t->dropColumn('budget_type'));
        Schema::table('budget_line_items',  fn($t) => $t->dropColumn('line_type'));
    }
};
