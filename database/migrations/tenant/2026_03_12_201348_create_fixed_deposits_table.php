<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_deposits', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('fd_number')->unique();
            $table->foreignId('member_id')->constrained('members');
            $table->foreignId('product_id')->constrained('savings_products');
            $table->foreignId('funding_account_id')->nullable()->constrained('savings_accounts'); // Source account

            // ─── Financial ──────────────────────────────
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 8, 4);                           // Rate locked at booking
            $table->decimal('interest_earned', 15, 2)->default(0.00);
            $table->decimal('maturity_amount', 15, 2)->default(0.00);         // Principal + interest at maturity

            // ─── Tenure ─────────────────────────────────
            $table->date('start_date');
            $table->date('maturity_date');
            $table->unsignedInteger('tenure_months');

            // ─── Rollover ───────────────────────────────
            $table->boolean('auto_rollover')->default(false);
            $table->string('rollover_type', 30)->nullable();                  // principal_only, principal_and_interest
            $table->unsignedTinyInteger('rollover_count')->default(0);
            $table->foreignId('rolled_from_id')->nullable()->constrained('fixed_deposits');

            // ─── Early Withdrawal ────────────────────────
            $table->boolean('is_broken')->default(false);
            $table->date('broken_date')->nullable();
            $table->decimal('early_withdrawal_penalty', 15, 2)->nullable();

            // ─── Status ─────────────────────────────────
            $table->string('status', 30)->default('active');                  // active, matured, rolled_over, terminated, broken

            // ─── Audit ──────────────────────────────────
            $table->string('branch_code')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('maturity_date');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_deposits');
    }
};
