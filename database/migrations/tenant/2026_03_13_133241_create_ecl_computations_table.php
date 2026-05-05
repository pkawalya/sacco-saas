<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecl_computations', function (Blueprint $table) {
            $table->id();

            // Period
            $table->string('computation_period', 10)->index()
                ->comment('YYYY-MM');
            $table->date('computation_date');

            // Portfolio totals
            $table->decimal('total_ead', 18, 2)->default(0);
            $table->decimal('total_ecl', 18, 2)->default(0);
            $table->decimal('provision_amount', 18, 2)->default(0);

            // Stage breakdown
            $table->unsignedInteger('stage_1_count')->default(0);
            $table->decimal('stage_1_ecl', 18, 2)->default(0);
            $table->unsignedInteger('stage_2_count')->default(0);
            $table->decimal('stage_2_ecl', 18, 2)->default(0);
            $table->unsignedInteger('stage_3_count')->default(0);
            $table->decimal('stage_3_ecl', 18, 2)->default(0);

            // Coverage
            $table->decimal('coverage_ratio', 8, 4)->default(0)
                ->comment('Total ECL / Total EAD');

            // GL posting
            $table->boolean('is_posted')->default(false);
            $table->string('journal_reference', 30)->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();

            $table->timestamps();

            $table->unique('computation_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecl_computations');
    }
};
