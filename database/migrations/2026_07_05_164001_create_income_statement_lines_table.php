<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_statement_config_id')
                  ->constrained('income_statement_configs')
                  ->cascadeOnDelete();
            $table->string('line_type'); // 'sub_category', 'subtotal', 'spacer'
            $table->foreignId('sub_category_id')
                  ->nullable()
                  ->constrained('account_sub_categories')
                  ->nullOnDelete();
            $table->string('label')->nullable();        // display label (overrides sub_category name)
            $table->string('operator')->nullable();     // 'add' or 'subtract'
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_statement_lines');
    }
};
