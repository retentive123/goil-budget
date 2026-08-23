<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsidiary_account_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subsidiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subsidiary_id', 'account_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidiary_account_codes');
    }
};
