<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratio_configs', function (Blueprint $table) {
            $table->json('numerator_codes')->nullable()->after('numerator_types');
            $table->json('denominator_codes')->nullable()->after('denominator_types');
        });
    }

    public function down(): void
    {
        Schema::table('ratio_configs', function (Blueprint $table) {
            $table->dropColumn(['numerator_codes', 'denominator_codes']);
        });
    }
};
