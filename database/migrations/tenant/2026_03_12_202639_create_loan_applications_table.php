<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('application_ref')->unique();
            $table->foreignId('member_id')->constrained('members');
            $table->foreignId('product_id')->constrained('loan_products');

            // ─── Requested Terms ─────────────────────────
            $table->decimal('amount_requested', 15, 2);
            $table->unsignedInteger('tenure_months_requested');
            $table->string('purpose', 100)->nullable();
            $table->text('purpose_details')->nullable();

            // ─── Appraisal & DSCR ────────────────────────
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->decimal('monthly_expenses', 15, 2)->nullable();
            $table->decimal('dscr', 8, 4)->nullable();                   // Debt Service Coverage Ratio
            $table->boolean('dscr_passed')->default(false);

            // ─── Recommendation ──────────────────────────
            $table->decimal('amount_recommended', 15, 2)->nullable();
            $table->unsignedInteger('tenure_months_recommended')->nullable();
            $table->unsignedBigInteger('recommended_by')->nullable();
            $table->text('officer_notes')->nullable();

            // ─── Status ─────────────────────────────────
            $table->string('status', 30)->default('draft');              // draft, submitted, under_review, approved, declined, withdrawn

            // ─── Audit ──────────────────────────────────
            $table->string('branch_code')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decision_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('member_id');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
