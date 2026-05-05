<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections_worklist', function (Blueprint $table) {
            $table->id();

            // Loan linkage
            $table->unsignedBigInteger('loan_id')->index();
            $table->string('loan_number', 30)->index();
            $table->string('member_name', 200);
            $table->unsignedBigInteger('member_id')->nullable()->index();

            // Delinquency metrics (FR-CE-001)
            $table->unsignedInteger('dpd')->default(0)->index()
                ->comment('Days Past Due');
            $table->decimal('arrears_amount', 18, 2)->default(0);
            $table->decimal('outstanding_balance', 18, 2)->default(0);
            $table->decimal('instalment_amount', 18, 2)->default(0);

            // Classification
            $table->string('delinquency_bucket', 20)->default('current')->index()
                ->comment('current | 1-30 | 31-60 | 61-90 | 91-180 | 180+');
            $table->unsignedTinyInteger('tier')->default(1)->index()
                ->comment('Escalation tier: 1=officer, 2=supervisor, 3=manager, 4=legal');
            $table->unsignedTinyInteger('previous_tier')->nullable();

            // Assignment
            $table->unsignedBigInteger('officer_id')->nullable()->index();
            $table->string('officer_name', 150)->nullable();
            $table->string('branch_code', 30)->nullable()->index();

            // Penalty (FR-CE-002)
            $table->decimal('penalty_rate', 5, 2)->default(0)
                ->comment('Daily penalty rate %');
            $table->decimal('accrued_penalty', 18, 2)->default(0);

            // Status
            $table->string('status', 20)->default('active')->index()
                ->comment('active | resolved | written_off | legal');
            $table->date('last_payment_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections_worklist');
    }
};
