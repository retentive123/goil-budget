<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('id')
                  ->constrained('zones')->nullOnDelete();
            $table->string('entity_type', 20)->default('department')->after('budget_type');
        });

        // Seed the default Head Office zone and assign all existing departments to it
        $zoneId = DB::table('zones')->insertGetId([
            'name'        => 'Head Office',
            'code'        => 'HQ',
            'description' => 'Head office departments',
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('departments')->whereNull('zone_id')->update(['zone_id' => $zoneId]);
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['zone_id', 'entity_type']);
        });

        DB::table('zones')->where('code', 'HQ')->delete();
    }
};
