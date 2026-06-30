<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('version_number'); // 1–4
            $table->enum('status', [
                'draft',        // being edited
                'submitted',    // sent for approval
                'under_review', // at an approval stage
                'approved',     // fully approved
                'rejected',     // rejected, revision needed
            ])->default('draft');
            $table->text('submission_notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Max 4 versions per dept per period
            $table->unique(['budget_period_id', 'department_id', 'version_number'], 'bv_period_dept_version_unique');;
        });
    }

    public function down(): void {
        Schema::dropIfExists('budget_versions');
    }
};
