<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('roles', function (Blueprint $table) {
            // all = can act on any dept, own = only their assigned dept
            $table->enum('scope', ['all', 'own'])->default('all')->after('guard_name');
            $table->boolean('can_partial_approve')->default(false)->after('scope');
            $table->boolean('can_reduce_amounts')->default(false)->after('can_partial_approve');
            $table->text('description')->nullable()->after('can_reduce_amounts');
        });
    }

    public function down(): void {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['scope','can_partial_approve','can_reduce_amounts','description']);
        });
    }
};
