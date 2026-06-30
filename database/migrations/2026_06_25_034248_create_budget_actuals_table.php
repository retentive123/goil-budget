<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_actuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1–12
            $table->year('year');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('reference')->nullable(); // e.g. invoice/voucher number
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->enum('status', ['draft','confirmed'])->default('draft');
            $table->timestamps();

            // One entry per line item per month
            $table->unique(
                ['budget_line_item_id','month','year'],
                'ba_line_month_year_unique'
            );
        });
    }

    public function down(): void {
        Schema::dropIfExists('budget_actuals');
    }
};
