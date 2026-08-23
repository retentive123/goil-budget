<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_versions', function (Blueprint $table) {
            // NULL  = original budget
            // true  = this version is a mid-year revision of `revised_from_id`
            $table->boolean('is_revision')->default(false)->after('version_number');
            $table->unsignedBigInteger('revised_from_id')->nullable()->after('is_revision');
            $table->text('revision_notes')->nullable()->after('revised_from_id');
            $table->timestamp('revised_at')->nullable()->after('revision_notes');
            $table->unsignedBigInteger('revised_by')->nullable()->after('revised_at');

            $table->foreign('revised_from_id')
                  ->references('id')
                  ->on('budget_versions')
                  ->nullOnDelete();

            $table->foreign('revised_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_versions', function (Blueprint $table) {
            $table->dropForeign(['revised_from_id']);
            $table->dropForeign(['revised_by']);
            $table->dropColumn(['is_revision', 'revised_from_id', 'revision_notes', 'revised_at', 'revised_by']);
        });
    }
};
