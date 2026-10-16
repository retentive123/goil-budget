<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_line_sub_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity',    15, 4)->nullable();
            $table->decimal('rate',        15, 4)->nullable();
            $table->decimal('frequency',   15, 4)->nullable();
            $table->decimal('m1_amount',   15, 2)->default(0);
            $table->decimal('m2_amount',   15, 2)->default(0);
            $table->decimal('m3_amount',   15, 2)->default(0);
            $table->decimal('m4_amount',   15, 2)->default(0);
            $table->decimal('m5_amount',   15, 2)->default(0);
            $table->decimal('m6_amount',   15, 2)->default(0);
            $table->decimal('m7_amount',   15, 2)->default(0);
            $table->decimal('m8_amount',   15, 2)->default(0);
            $table->decimal('m9_amount',   15, 2)->default(0);
            $table->decimal('m10_amount',  15, 2)->default(0);
            $table->decimal('m11_amount',  15, 2)->default(0);
            $table->decimal('m12_amount',  15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_line_sub_entries');
    }
};
