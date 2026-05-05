<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecl_staging', function (Blueprint $table) {
            $table->id();

            // Loan linkage
            $table->unsignedBigInteger('loan_id')->index();
            $table->string('loan_number', 30)->index();

            // IFRS 9 staging
            $table->unsignedTinyInteger('stage')->default(1)
                ->comment('1=performing, 2=significant increase, 3=credit-impaired');
            $table->unsignedTinyInteger('previous_stage')->nullable();
            $table->timestamp('stage_changed_at')->nullable();

            // Risk parameters
            $table->decimal('pd', 8, 6)->default(0)
                ->comment('Probability of Default');
            $table->decimal('lgd', 8, 6)->default(0.45)
                ->comment('Loss Given Default');
            $table->decimal('ead', 18, 2)->default(0)
                ->comment('Exposure at Default');
            $table->unsignedSmallInteger('dpd')->default(0)
                ->comment('Days Past Due');

            // Computed
            $table->decimal('ecl_amount', 18, 2)->default(0)
                ->comment('PD × LGD × EAD');
            $table->string('computation_period', 10)->nullable()
                ->comment('YYYY-MM format');

            $table->timestamps();

            $table->unique(['loan_id', 'computation_period'], 'ecl_loan_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecl_staging');
    }
};
