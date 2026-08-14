<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->decimal('default_rate',      15, 4)->nullable()->after('sort_order')
                  ->comment('Admin-set default rate; used when admin_sets_rate setting is on');
            $table->decimal('default_frequency', 10, 4)->nullable()->after('default_rate')
                  ->comment('Admin-set default frequency; used when admin_sets_freq setting is on');
        });
    }

    public function down(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->dropColumn(['default_rate', 'default_frequency']);
        });
    }
};
