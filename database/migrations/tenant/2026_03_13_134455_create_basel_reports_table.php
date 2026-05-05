<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basel_reports', function (Blueprint $table) {
            $table->id();

            $table->string('report_ref', 30)->unique();
            $table->string('report_type', 30)->index()
                ->comment('capital_adequacy | liquidity_coverage | net_stable_funding | leverage_ratio');
            $table->string('reporting_period', 10)
                ->comment('YYYY-MM or YYYY-QN');

            // Capital adequacy (Basel III)
            $table->decimal('tier_1_capital', 18, 2)->default(0);
            $table->decimal('tier_2_capital', 18, 2)->default(0);
            $table->decimal('total_capital', 18, 2)->default(0);
            $table->decimal('risk_weighted_assets', 18, 2)->default(0);
            $table->decimal('car_ratio', 8, 4)->default(0)
                ->comment('Capital Adequacy Ratio %');
            $table->decimal('minimum_car', 8, 4)->default(12.0)
                ->comment('BOU minimum 12%');

            // Liquidity
            $table->decimal('hqla', 18, 2)->default(0)
                ->comment('High Quality Liquid Assets');
            $table->decimal('net_cash_outflows', 18, 2)->default(0);
            $table->decimal('lcr_ratio', 8, 4)->default(0)
                ->comment('Liquidity Coverage Ratio %');

            // Status
            $table->boolean('is_compliant')->default(true);
            $table->boolean('is_submitted')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['report_type', 'reporting_period'], 'basel_type_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basel_reports');
    }
};
