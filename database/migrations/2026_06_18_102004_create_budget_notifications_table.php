<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');           // e.g. "budget_submitted"
            $table->string('subject');
            $table->text('message');
            $table->morphs('notifiable');     // links to budget_versions, virements, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('budget_notifications');
    }
};
