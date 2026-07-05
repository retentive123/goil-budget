<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_statement_lines', function (Blueprint $table) {
            $table->string('cs_base_subtotal_label')->nullable()->after('cs_base_sub_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('income_statement_lines', function (Blueprint $table) {
            $table->dropColumn('cs_base_subtotal_label');
        });
    }
};
