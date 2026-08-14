<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('budget_line_items', function (Blueprint $table) {
            // Qty × Rate × Frequency calculation fields
            // Stored so reports can show the breakdown; actual amounts are still in m1–m12
            $table->decimal('quantity',  15, 4)->nullable()->after('m12_amount')
                  ->comment('Quantity used to compute line total (Qty × Rate [× Freq])');
            $table->decimal('rate',      15, 4)->nullable()->after('quantity')
                  ->comment('Unit rate; may be admin-locked via system setting');
            $table->decimal('frequency', 10, 4)->nullable()->after('rate')
                  ->comment('Recurrence multiplier (e.g. 12 = monthly, 4 = quarterly)');
        });
    }

    public function down(): void
    {
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'rate', 'frequency']);
        });
    }
};
