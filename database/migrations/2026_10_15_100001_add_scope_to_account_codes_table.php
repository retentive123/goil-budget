<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->string('scope', 20)->default('department')->after('is_active');
        });

        // Drop the old global unique on code and replace with (code, scope)
        Schema::table('account_codes', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->dropUnique(['code', 'scope']);
            $table->dropColumn('scope');
        });

        Schema::table('account_codes', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
