<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('receipt_number')->unique();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('member_id')->constrained('members');

            // ─── Payment ────────────────────────────────
            $table->decimal('amount_paid', 15, 2);
            $table->string('channel', 30)->default('branch');            // branch, mobile, ussd, agent, payroll, standing_order
            $table->string('reference_number')->nullable();              // Mobile money ref, bank ref, etc.

            // ─── Allocation Breakdown (FR-LM-031) ────────
            // Allocation order is configurable per product: penalty → interest → principal
            $table->decimal('allocated_to_penalty', 15, 2)->default(0);
            $table->decimal('allocated_to_interest', 15, 2)->default(0);
            $table->decimal('allocated_to_principal', 15, 2)->default(0);
            $table->decimal('allocated_to_fees', 15, 2)->default(0);
            $table->decimal('excess_amount', 15, 2)->default(0);         // Refund or advance payment

            // ─── Reversal ───────────────────────────────
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversal_of')->nullable()->constrained('loan_repayments');
            $table->text('reversal_reason')->nullable();

            // ─── Running Balance ─────────────────────────
            $table->decimal('outstanding_after', 15, 2)->default(0);     // Loan balance after this repayment

            // ─── Audit ──────────────────────────────────
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->date('value_date');
            $table->timestamp('posted_at');

            $table->timestamps();

            $table->index('channel');
            $table->index('value_date');
            $table->index('is_reversed');
            $table->index('loan_id');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
