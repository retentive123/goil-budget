<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->decimal('default_rate',      15, 4)->nullable()->after('description');
            $table->decimal('default_frequency', 10, 4)->nullable()->after('default_rate');
        });
    }

    public function down(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropColumn(['default_rate', 'default_frequency']);
        });
    }
};
