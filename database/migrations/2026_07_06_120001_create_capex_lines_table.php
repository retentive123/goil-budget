<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capex_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capex_config_id')->constrained()->cascadeOnDelete();
            $table->string('line_type'); // sub_category | subtotal | spacer
            $table->foreignId('sub_category_id')
                  ->nullable()
                  ->constrained('account_sub_categories')
                  ->nullOnDelete();
            $table->string('label')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capex_lines');
    }
};
