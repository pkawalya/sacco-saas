<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('loan_number')->unique();
            $table->foreignId('member_id')->constrained('members');
            $table->foreignId('product_id')->constrained('loan_products');
            $table->foreignId('application_id')->nullable()->constrained('loan_applications');

            // ─── Principal & Terms ───────────────────────
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2);
            $table->decimal('disbursed_amount', 15, 2)->default(0);
            $table->unsignedInteger('tenure_months');
            $table->decimal('interest_rate', 8, 4);                      // Rate locked at approval
            $table->string('interest_method', 30);                       // reducing, flat

            // ─── Fees (computed at booking) ───────────────
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('insurance_amount', 15, 2)->default(0);
            $table->decimal('total_fees', 15, 2)->default(0);

            // ─── Balance Tracking ────────────────────────
            $table->decimal('outstanding_principal', 15, 2)->default(0);
            $table->decimal('outstanding_interest', 15, 2)->default(0);
            $table->decimal('outstanding_penalty', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);

            // ─── Repayment ──────────────────────────────
            $table->decimal('monthly_instalment', 15, 2)->default(0);
            $table->date('first_repayment_date')->nullable();
            $table->date('expected_maturity_date')->nullable();
            $table->date('actual_maturity_date')->nullable();
            $table->date('last_repayment_date')->nullable();

            // ─── PAR (Days Past Due) ─────────────────────
            $table->unsignedInteger('days_past_due')->default(0);        // Recomputed nightly
            $table->string('par_bucket', 10)->default('current');        // current, 1-30, 31-60, 61-90, 91-180, 180+
            $table->decimal('amount_in_arrears', 15, 2)->default(0);

            // ─── Disbursement ────────────────────────────
            $table->string('disbursement_account', 50)->nullable();      // savings account or mobile number
            $table->string('disbursement_channel', 30)->nullable();
            $table->unsignedBigInteger('disbursed_by')->nullable();
            $table->unsignedBigInteger('authorised_by')->nullable();     // Four-eyes second approver
            $table->timestamp('disbursed_at')->nullable();

            // ─── Status & Lifecycle ──────────────────────
            $table->string('status', 30)->default('approved');           // approved, active, completed, written_off, restructured
            $table->string('branch_code')->nullable();
            $table->unsignedBigInteger('loan_officer_id')->nullable();

            // ─── Top-up / Restructure ────────────────────
            $table->foreignId('parent_loan_id')->nullable()->constrained('loans');
            $table->string('loan_type', 20)->default('original');        // original, top_up, restructured

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('par_bucket');
            $table->index('days_past_due');
            $table->index('branch_code');
            $table->index('member_id');
            $table->index('loan_officer_id');
            $table->index('expected_maturity_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
