<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_allocations', function (Blueprint $table) {
            $table->id();

            // Linkage
            $table->foreignId('cost_centre_id')
                ->constrained('cost_centres')
                ->cascadeOnDelete();
            $table->foreignId('gl_account_id')
                ->constrained('chart_of_accounts')
                ->restrictOnDelete();

            // Period
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('period_month')->nullable()
                ->comment('1-12 for monthly, null for annual');

            // Amounts
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->decimal('actual_amount', 18, 2)->default(0);

            // Allocation method (FR-CC-003: charge-backs & transfer pricing)
            $table->string('allocation_method', 30)->default('direct')
                ->comment('direct | proportional | headcount | revenue_based | activity_based');
            $table->decimal('allocation_percentage', 8, 4)->default(100.0000)
                ->comment('Percentage of the GL account allocated to this cost centre');

            // Internal charge-back (FR-CC-003)
            $table->foreignId('chargeback_from_id')
                ->nullable()
                ->constrained('cost_centres')
                ->nullOnDelete()
                ->comment('Source cost centre if this is an internal charge-back');
            $table->decimal('transfer_price', 18, 2)->default(0)
                ->comment('Transfer pricing amount for internal charge-backs');
            $table->text('chargeback_description')->nullable();

            // Status
            $table->string('status', 20)->default('active')
                ->comment('active | frozen | closed');

            $table->timestamps();

            $table->unique(['cost_centre_id', 'gl_account_id', 'fiscal_year', 'period_month'], 'cc_gl_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_allocations');
    }
};
