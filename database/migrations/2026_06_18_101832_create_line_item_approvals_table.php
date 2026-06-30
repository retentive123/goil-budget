<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('line_item_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_decision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_line_item_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['approved', 'rejected', 'reduced']);
            $table->decimal('approved_amount', 15, 2)->nullable(); // if reduced
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('line_item_approvals');
    }
};
