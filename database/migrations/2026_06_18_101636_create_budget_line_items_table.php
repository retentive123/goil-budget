<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained()->restrictOnDelete();
            $table->decimal('q1_amount', 15, 2)->default(0);
            $table->decimal('q2_amount', 15, 2)->default(0);
            $table->decimal('q3_amount', 15, 2)->default(0);
            $table->decimal('q4_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->storedAs('q1_amount + q2_amount + q3_amount + q4_amount');
            $table->text('justification')->nullable();
            $table->foreignId('last_updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['budget_version_id', 'account_code_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('budget_line_items');
    }
};
