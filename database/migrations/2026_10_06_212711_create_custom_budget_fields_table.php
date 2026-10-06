<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_budget_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');                            // machine key
            $table->string('label');                           // display label
            $table->string('field_type')->default('text');    // text | number | select | boolean
            $table->json('options')->nullable();               // for select type: ['Option A','Option B']
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->string('placeholder')->nullable();
            $table->timestamps();
        });

        Schema::create('budget_line_item_custom_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_budget_field_id')->constrained('custom_budget_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['budget_line_item_id', 'custom_budget_field_id'], 'cbf_item_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_line_item_custom_values');
        Schema::dropIfExists('custom_budget_fields');
    }
};
