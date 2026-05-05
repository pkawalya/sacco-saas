<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('budget_code', 30)->unique();
            $table->string('budget_name', 150);
            $table->text('description')->nullable();

            // GL + Cost Centre linkage
            $table->foreignId('gl_account_id')
                ->constrained('chart_of_accounts')
                ->restrictOnDelete();
            $table->string('cost_centre_code', 30)->nullable()->index();

            // Period
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('period', 20)->default('annual')
                ->comment('annual | q1 | q2 | q3 | q4 | monthly');
            $table->date('start_date');
            $table->date('end_date');

            // 3-tier amounts (FR-RE-020–024)
            $table->decimal('original_amount', 18, 2)->default(0)
                ->comment('Tier 1: original approved budget');
            $table->decimal('revised_amount', 18, 2)->default(0)
                ->comment('Tier 2: revised/supplementary budget');
            $table->decimal('approved_amount', 18, 2)->default(0)
                ->comment('Tier 3: final approved amount');
            $table->decimal('actual_amount', 18, 2)->default(0)
                ->comment('Actual spend/revenue to date');

            // Status & approval
            $table->string('status', 20)->default('draft')->index()
                ->comment('draft | submitted | approved | active | closed');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Variance controls
            $table->decimal('variance_threshold_pct', 5, 2)->default(10.00)
                ->comment('Alert when actual exceeds this % of approved');
            $table->boolean('enforce_budget')->default(false)
                ->comment('Block transactions exceeding budget');

            $table->timestamps();

            $table->index(['fiscal_year', 'gl_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
