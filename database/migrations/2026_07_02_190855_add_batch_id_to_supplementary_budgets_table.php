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
        Schema::table('supplementary_budgets', function (Blueprint $table) {
            $table->string('batch_id', 36)->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('supplementary_budgets', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};
