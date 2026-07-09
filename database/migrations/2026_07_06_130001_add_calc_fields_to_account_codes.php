<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->string('unit', 50)->nullable()->after('description');
            $table->string('calc_type', 20)->default('values')->after('unit'); // values | calculation
            $table->json('calc_config')->nullable()->after('calc_type');
            $table->smallInteger('sort_order')->default(0)->after('calc_config');
        });
    }

    public function down(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->dropColumn(['unit', 'calc_type', 'calc_config', 'sort_order']);
        });
    }
};
