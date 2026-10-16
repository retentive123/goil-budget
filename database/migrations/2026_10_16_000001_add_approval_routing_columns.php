<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_stages', function (Blueprint $table) {
            // null = always runs; 'gceo' / 'coo' = branch-specific
            $table->string('route_key')->nullable()->default(null)->after('role_name');
        });

        Schema::table('departments', function (Blueprint $table) {
            // 'gceo' | 'coo' | 'direct' (skip middle stage entirely)
            $table->string('approval_route')->default('gceo')->after('entity_type');
        });
    }

    public function down(): void
    {
        Schema::table('approval_stages', function (Blueprint $table) {
            $table->dropColumn('route_key');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('approval_route');
        });
    }
};
