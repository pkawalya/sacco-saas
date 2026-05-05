<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_claims', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('claim_number', 30)->unique();
            $table->string('claimant_name', 150);
            $table->unsignedBigInteger('claimant_user_id')->nullable()->index();

            // Category & GL
            $table->string('category', 50)->index()
                ->comment('travel | training | supplies | utilities | telephone | other');
            $table->foreignId('gl_account_id')
                ->constrained('chart_of_accounts')
                ->restrictOnDelete();
            $table->string('cost_centre_code', 30)->nullable()->index();

            // Amounts
            $table->decimal('claimed_amount', 18, 2);
            $table->decimal('approved_amount', 18, 2)->nullable()
                ->comment('May differ from claimed after review');
            $table->string('currency', 3)->default('UGX');

            // Supporting info
            $table->text('description');
            $table->date('expense_date');
            $table->string('receipt_path', 500)->nullable()->comment('File path to receipt/invoice');
            $table->json('line_items')->nullable()
                ->comment('[{description, amount, receipt_ref}]');

            // Workflow
            $table->string('status', 20)->default('draft')->index()
                ->comment('draft | submitted | under_review | approved | rejected | paid');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Budget linkage
            $table->foreignId('budget_id')
                ->nullable()
                ->constrained('budgets')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claims');
    }
};
