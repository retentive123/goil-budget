<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratio_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();

            // Numerator
            $table->string('numerator_source')->default('budget'); // budget | actual
            $table->json('numerator_types');                       // ["revenue"] | ["expense"] | ["all"] etc.

            // Denominator
            $table->string('denominator_source')->default('budget');
            $table->json('denominator_types');

            // Presentation
            $table->decimal('multiply_by', 12, 4)->default(100);  // 100 = %, 1 = raw ratio
            $table->string('unit')->default('%');                   // %, ×, etc.
            $table->boolean('higher_is_better')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed defaults
        DB::table('ratio_configs')->insert([
            [
                'name'               => 'Budget Utilisation Rate',
                'description'        => 'Actual spend as a percentage of approved budget',
                'numerator_source'   => 'actual',
                'numerator_types'    => json_encode(['all']),
                'denominator_source' => 'budget',
                'denominator_types'  => json_encode(['all']),
                'multiply_by'        => 100,
                'unit'               => '%',
                'higher_is_better'   => false,
                'is_active'          => true,
                'sort_order'         => 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Expense to Revenue Ratio',
                'description'        => 'Total budgeted expenses as a percentage of total budgeted revenue',
                'numerator_source'   => 'budget',
                'numerator_types'    => json_encode(['expense', 'both']),
                'denominator_source' => 'budget',
                'denominator_types'  => json_encode(['revenue', 'both']),
                'multiply_by'        => 100,
                'unit'               => '%',
                'higher_is_better'   => false,
                'is_active'          => true,
                'sort_order'         => 2,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'CapEx Intensity',
                'description'        => 'Capital expenditure as a percentage of total revenue budget',
                'numerator_source'   => 'budget',
                'numerator_types'    => json_encode(['capital_expenditure']),
                'denominator_source' => 'budget',
                'denominator_types'  => json_encode(['revenue', 'both']),
                'multiply_by'        => 100,
                'unit'               => '%',
                'higher_is_better'   => false,
                'is_active'          => true,
                'sort_order'         => 3,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Operating Cost Ratio',
                'description'        => 'Operating expenses as a share of total approved budget',
                'numerator_source'   => 'budget',
                'numerator_types'    => json_encode(['expense']),
                'denominator_source' => 'budget',
                'denominator_types'  => json_encode(['all']),
                'multiply_by'        => 100,
                'unit'               => '%',
                'higher_is_better'   => false,
                'is_active'          => true,
                'sort_order'         => 4,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Actual vs Budget Variance',
                'description'        => 'Actual spend compared to budget — positive means over budget',
                'numerator_source'   => 'actual',
                'numerator_types'    => json_encode(['all']),
                'denominator_source' => 'budget',
                'denominator_types'  => json_encode(['all']),
                'multiply_by'        => 100,
                'unit'               => '%',
                'higher_is_better'   => false,
                'is_active'          => true,
                'sort_order'         => 5,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ratio_configs');
    }
};
