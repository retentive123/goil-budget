<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submission_deadline_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->text('reason');
            $table->timestamp('new_deadline')->nullable(); // null = no specific deadline extension
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['budget_period_id','department_id'], 'sdo_period_dept_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('submission_deadline_overrides');
    }
};
