<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_products', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('product_code')->unique();
            $table->string('product_name');
            $table->string('product_type', 50)->default('regular'); // regular, fixed_deposit, current, holiday, children
            $table->text('description')->nullable();

            // ─── Interest ───────────────────────────────
            $table->decimal('interest_rate', 8, 4)->default(0.0000);         // Annual rate (%)
            $table->string('interest_computation', 50)->default('daily_average'); // daily_average, min_monthly, eom_balance
            $table->string('interest_posting_cycle', 30)->default('monthly'); // monthly, quarterly, annually
            $table->boolean('has_tiered_rates')->default(false);
            $table->json('tier_rates')->nullable();                            // [{"min_balance": 0, "max_balance": 999999, "rate": 3.5}, ...]

            // ─── Limits ─────────────────────────────────
            $table->decimal('minimum_balance', 15, 2)->default(0.00);
            $table->decimal('maximum_balance', 15, 2)->nullable();
            $table->decimal('minimum_opening_deposit', 15, 2)->default(0.00);
            $table->decimal('maximum_single_deposit', 15, 2)->nullable();
            $table->decimal('maximum_single_withdrawal', 15, 2)->nullable();
            $table->unsignedInteger('free_withdrawals_per_month')->default(0);

            // ─── Penalties ──────────────────────────────
            $table->decimal('below_minimum_penalty', 15, 2)->default(0.00);  // Per month
            $table->decimal('early_withdrawal_penalty_rate', 8, 4)->default(0.0000); // For FDs (%)

            // ─── Fixed Deposit Specific ──────────────────
            $table->unsignedInteger('minimum_tenure_months')->nullable();
            $table->unsignedInteger('maximum_tenure_months')->nullable();
            $table->boolean('auto_rollover')->default(false);

            // ─── Status ─────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_joint_allowed')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_products');
    }
};
