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
        Schema::table('income_statement_configs', function (Blueprint $table) {
            $table->foreignId('base_sub_category_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('account_sub_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('income_statement_configs', function (Blueprint $table) {
            $table->dropForeign(['base_sub_category_id']);
            $table->dropColumn('base_sub_category_id');
        });
    }
};
