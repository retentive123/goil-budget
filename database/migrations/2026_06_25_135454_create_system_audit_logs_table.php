<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('system_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');           // login, logout, export, settings_change etc.
            $table->string('module');          // auth, budget, approval, virement, settings
            $table->string('action');          // created, updated, deleted, approved, exported
            $table->string('subject_type')->nullable();  // model class
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable(); // human-readable e.g. "Budget v2 - Finance Dept"
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable();  // extra context: IP, user agent, export type etc.
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('severity', ['info','warning','critical'])->default('info');
            $table->timestamps();

            $table->index(['module', 'event']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('system_audit_logs');
    }
};
