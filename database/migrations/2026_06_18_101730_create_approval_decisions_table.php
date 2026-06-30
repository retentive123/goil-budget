<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approval_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('decided_by')->constrained('users');
            $table->enum('decision', ['approved', 'rejected', 'partial']);
            $table->text('comments')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            // One decision per stage per version
            $table->unique(['budget_version_id', 'approval_stage_id'], 'ad_version_stage_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('approval_decisions');
    }
};
