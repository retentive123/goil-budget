<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expump_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expump_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expump_code_id')->constrained('account_codes')->cascadeOnDelete();
            $table->foreignId('revenue_code_id')->constrained('account_codes')->cascadeOnDelete();
            $table->decimal('value', 18, 6)->nullable();
            $table->timestamps();

            $table->unique(['expump_template_id', 'expump_code_id', 'revenue_code_id'], 'expump_values_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expump_values');
    }
};
