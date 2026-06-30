<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->enum('type', ['manual','scheduled','pre_deploy'])->default('manual');
            $table->enum('status', ['pending','running','completed','failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('system_backups');
    }
};
