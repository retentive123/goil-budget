<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('departments', function (Blueprint $table) {
            $table->enum('budget_type', ['revenue','expense','both'])
                  ->default('expense')
                  ->after('is_active');
        });
    }

    public function down(): void {
        Schema::table('departments', fn($t) => $t->dropColumn('budget_type'));
    }
};
