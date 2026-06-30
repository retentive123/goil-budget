<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('supplementary_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_line_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained()->cascadeOnDelete();
            $table->enum('line_type', ['revenue','expense'])->default('expense');
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->decimal('requested_amount', 15, 2);   // additional amount needed
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('justification');
            $table->text('supporting_evidence')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'approved',
                'rejected',
            ])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['budget_period_id','department_id','status'],
                'sb_period_dept_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('supplementary_budgets');
    }
};
