<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratio_configs', function (Blueprint $table) {
            $table->json('numerator_category_ids')->nullable()->after('numerator_codes');
            $table->json('denominator_category_ids')->nullable()->after('denominator_codes');
        });
    }

    public function down(): void
    {
        Schema::table('ratio_configs', function (Blueprint $table) {
            $table->dropColumn(['numerator_category_ids', 'denominator_category_ids']);
        });
    }
};
