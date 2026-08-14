<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-period snapshot of calc-mode settings.
 * Decoupling these from the global system_settings table means:
 *  - Different periods can have different entry methods.
 *  - Once a period is used, its settings are preserved for historical audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_period_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')
                  ->unique()
                  ->constrained('budget_periods')
                  ->cascadeOnDelete();

            // Mirrors the three global system_settings, but scoped to this period
            $table->string('line_item_calc_mode')->default('none');   // none | qty_rate | qty_rate_freq
            $table->boolean('admin_sets_rate')->default(false);
            $table->boolean('admin_sets_freq')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_period_settings');
    }
};
