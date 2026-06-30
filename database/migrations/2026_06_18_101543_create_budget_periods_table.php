<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "FY 2026"
            $table->year('year');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', [
                'draft',      // not yet open
                'open',       // departments can enter budgets
                'closed',     // submission deadline passed
                'approved',   // board has given final approval
            ])->default('draft');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('budget_periods');
    }
};
