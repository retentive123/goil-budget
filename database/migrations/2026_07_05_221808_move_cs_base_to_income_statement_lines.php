<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove global base from configs
        Schema::table('income_statement_configs', function (Blueprint $table) {
            $table->dropForeign(['base_sub_category_id']);
            $table->dropColumn('base_sub_category_id');
        });

        // Add per-line base to lines
        Schema::table('income_statement_lines', function (Blueprint $table) {
            $table->foreignId('cs_base_sub_category_id')
                  ->nullable()
                  ->after('sort_order')
                  ->constrained('account_sub_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('income_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['cs_base_sub_category_id']);
            $table->dropColumn('cs_base_sub_category_id');
        });

        Schema::table('income_statement_configs', function (Blueprint $table) {
            $table->foreignId('base_sub_category_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('account_sub_categories')
                  ->nullOnDelete();
        });
    }
};
