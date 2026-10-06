<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_settings', function (Blueprint $table) {
            $table->id();
            $table->string('role');           // e.g. 'finance_reviewer', 'department_head', '__all__'
            $table->string('widget_key');     // e.g. 'budget_health', 'analytics'
            $table->boolean('is_visible')->default(true);
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['role', 'widget_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_settings');
    }
};
