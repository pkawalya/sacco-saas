<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('product_code')->unique();
            $table->string('product_name');
            $table->string('product_type', 50)->default('term');        // term, revolving, mortgage, group, emergency, school_fees
            $table->text('description')->nullable();

            // ─── Interest ───────────────────────────────
            $table->decimal('interest_rate', 8, 4);                     // Annual percentage
            $table->string('interest_method', 30)->default('reducing');  // reducing, flat, compound
            $table->string('interest_period', 20)->default('monthly');   // daily, weekly, monthly

            // ─── Fees ───────────────────────────────────
            $table->decimal('processing_fee_rate', 8, 4)->default(0);   // % of principal
            $table->decimal('processing_fee_fixed', 15, 2)->default(0); // Flat fee
            $table->boolean('processing_fee_upfront')->default(true);
            $table->decimal('maintenance_fee_monthly', 15, 2)->default(0);
            $table->decimal('insurance_rate', 8, 4)->default(0);         // % of principal (life/credit)

            // ─── Penalty ────────────────────────────────
            $table->decimal('penalty_rate_daily', 8, 4)->default(0);    // % of overdue balance per day
            $table->decimal('penalty_rate_monthly', 8, 4)->default(0);  // % of overdue balance per month
            $table->unsignedInteger('grace_period_days')->default(0);

            // ─── Tenure ─────────────────────────────────
            $table->unsignedInteger('minimum_tenure_months')->default(1);
            $table->unsignedInteger('maximum_tenure_months')->default(60);

            // ─── Amount Limits ───────────────────────────
            $table->decimal('minimum_amount', 15, 2)->default(0);
            $table->decimal('maximum_amount', 15, 2)->nullable();
            $table->decimal('maximum_multiplier', 8, 4)->nullable();     // e.g. 3× savings

            // ─── Guarantor & Collateral Rules ────────────
            $table->unsignedInteger('minimum_guarantors')->default(0);
            $table->unsignedInteger('maximum_guarantors')->default(0);
            $table->boolean('collateral_required')->default(false);
            $table->decimal('minimum_coverage_ratio', 8, 4)->default(1.00); // LTV inverted

            // ─── Approval & Disbursement ─────────────────
            $table->json('approval_levels')->nullable();                  // [{'level': 1, 'min': 0, 'max': 999999, 'roles': ['loan_officer']}, ...]
            $table->boolean('four_eyes_disbursement')->default(true);

            // ─── Status ─────────────────────────────────
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
