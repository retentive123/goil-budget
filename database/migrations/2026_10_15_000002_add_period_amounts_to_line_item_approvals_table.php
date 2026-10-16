<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('line_item_approvals', function (Blueprint $table) {
            $table->decimal('m1_amount',  15, 2)->nullable()->after('approved_amount');
            $table->decimal('m2_amount',  15, 2)->nullable()->after('m1_amount');
            $table->decimal('m3_amount',  15, 2)->nullable()->after('m2_amount');
            $table->decimal('m4_amount',  15, 2)->nullable()->after('m3_amount');
            $table->decimal('m5_amount',  15, 2)->nullable()->after('m4_amount');
            $table->decimal('m6_amount',  15, 2)->nullable()->after('m5_amount');
            $table->decimal('m7_amount',  15, 2)->nullable()->after('m6_amount');
            $table->decimal('m8_amount',  15, 2)->nullable()->after('m7_amount');
            $table->decimal('m9_amount',  15, 2)->nullable()->after('m8_amount');
            $table->decimal('m10_amount', 15, 2)->nullable()->after('m9_amount');
            $table->decimal('m11_amount', 15, 2)->nullable()->after('m10_amount');
            $table->decimal('m12_amount', 15, 2)->nullable()->after('m11_amount');
        });
    }

    public function down(): void {
        Schema::table('line_item_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'm1_amount', 'm2_amount', 'm3_amount', 'm4_amount',
                'm5_amount', 'm6_amount', 'm7_amount', 'm8_amount',
                'm9_amount', 'm10_amount', 'm11_amount', 'm12_amount',
            ]);
        });
    }
};
