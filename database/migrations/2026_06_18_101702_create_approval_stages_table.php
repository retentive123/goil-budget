<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approval_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "Department Head"
            $table->unsignedTinyInteger('order'); // 1=DeptHead, 2=Finance, 3=GCEO, 4=Board
            $table->string('role_name');      // matches Spatie role name
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('approval_stages');
    }
};
