<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_sheet_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balance_sheet_config_id')
                  ->constrained('balance_sheet_configs')->cascadeOnDelete();
            $table->string('line_type'); // sub_category | subtotal | spacer
            $table->foreignId('sub_category_id')
                  ->nullable()->constrained('account_sub_categories')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('section')->nullable(); // assets | liabilities (required for subtotals)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_sheet_lines');
    }
};
