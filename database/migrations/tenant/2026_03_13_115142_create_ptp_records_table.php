<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptp_records', function (Blueprint $table) {
            $table->id();

            // Linkage
            $table->foreignId('worklist_id')
                ->constrained('collections_worklist')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('loan_id')->index();
            $table->string('loan_number', 30)->index();

            // PTP details (FR-CE-011)
            $table->decimal('promised_amount', 18, 2);
            $table->date('promised_date');
            $table->decimal('actual_amount_paid', 18, 2)->default(0);
            $table->date('actual_payment_date')->nullable();

            // Status
            $table->string('status', 20)->default('pending')->index()
                ->comment('pending | kept | broken | partial');
            $table->boolean('is_broken')->default(false)->index();
            $table->timestamp('broken_flagged_at')->nullable();

            // Officer
            $table->unsignedBigInteger('captured_by')->nullable();
            $table->string('officer_name', 150)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptp_records');
    }
};
