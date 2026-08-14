<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_period_code_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('budget_period_id')
                  ->constrained('budget_periods')
                  ->cascadeOnDelete();

            $table->foreignId('account_code_id')
                  ->constrained('account_codes')
                  ->cascadeOnDelete();

            // null = not set for this period; fall back to global account_code default
            $table->decimal('default_rate',      15, 4)->nullable();
            $table->decimal('default_frequency', 10, 4)->nullable();

            $table->timestamps();

            $table->unique(['budget_period_id', 'account_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_period_code_rates');
    }
};
