<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('virements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_line_item_id')->constrained('budget_line_items')->restrictOnDelete();
            $table->foreignId('to_line_item_id')->constrained('budget_line_items')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('justification');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('approval_comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('virements');
    }
};
