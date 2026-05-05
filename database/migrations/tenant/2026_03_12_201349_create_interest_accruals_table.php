<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_accruals', function (Blueprint $table) {
            $table->id();

            // ─── Linking ────────────────────────────────
            $table->foreignId('account_id')->constrained('savings_accounts');
            $table->foreignId('product_id')->constrained('savings_products');
            $table->foreignId('member_id')->constrained('members');

            // ─── Period ─────────────────────────────────
            $table->date('accrual_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('computation_method', 50);                         // daily_average, min_monthly, eom_balance

            // ─── Calculation Inputs ──────────────────────
            $table->decimal('average_balance', 15, 2);
            $table->decimal('applicable_rate', 8, 4);                         // Rate used (may vary if mid-period change)
            $table->unsignedInteger('days_in_period');

            // ─── Result ─────────────────────────────────
            $table->decimal('accrual_amount', 15, 4);                         // Precise to 4dp

            // ─── Posting ────────────────────────────────
            $table->string('posting_status', 20)->default('pending');         // pending, posted, reversed
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('savings_transactions');

            $table->timestamps();

            $table->index('accrual_date');
            $table->index('posting_status');
            $table->index(['account_id', 'period_start', 'period_end'], 'ia_account_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_accruals');
    }
};
