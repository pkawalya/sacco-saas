<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amortisation_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained('loans');
            $table->unsignedSmallInteger('instalment_number');           // 1, 2, 3...
            $table->date('due_date');

            // ─── Scheduled Amounts ───────────────────────
            $table->decimal('principal_due', 15, 2)->default(0);
            $table->decimal('interest_due', 15, 2)->default(0);
            $table->decimal('maintenance_fee_due', 15, 2)->default(0);
            $table->decimal('total_due', 15, 2)->default(0);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);

            // ─── Actual Payments ──────────────────────────
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('penalty_paid', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->date('paid_date')->nullable();

            // ─── Status ─────────────────────────────────
            $table->string('status', 20)->default('scheduled');          // scheduled, paid, partial, overdue, waived

            $table->timestamps();

            $table->unique(['loan_id', 'instalment_number']);
            $table->index('due_date');
            $table->index('status');
            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amortisation_schedules');
    }
};
