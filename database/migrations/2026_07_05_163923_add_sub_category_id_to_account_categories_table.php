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
        Schema::table('account_categories', function (Blueprint $table) {
            $table->foreignId('account_sub_category_id')
                  ->nullable()
                  ->after('budget_type')
                  ->constrained('account_sub_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropForeign(['account_sub_category_id']);
            $table->dropColumn('account_sub_category_id');
        });
    }
};
