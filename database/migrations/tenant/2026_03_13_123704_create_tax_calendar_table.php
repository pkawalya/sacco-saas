<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_calendar', function (Blueprint $table) {
            $table->id();

            // Identity (FR-RC-021-022)
            $table->string('tax_type', 30)->index()
                ->comment('paye | vat | withholding_tax | corporate_tax | excise_duty');
            $table->string('description', 200);

            // Period
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('period_month')->nullable();
            $table->date('period_start');
            $table->date('period_end');

            // Deadline
            $table->date('due_date');
            $table->unsignedSmallInteger('reminder_days_before')->default(5);

            // Amounts (FR-RC-021)
            $table->decimal('computed_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('penalty_amount', 18, 2)->default(0);

            // Filing
            $table->string('filing_status', 20)->default('upcoming')->index()
                ->comment('upcoming | due | filed | paid | overdue');
            $table->date('filed_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('receipt_number', 100)->nullable();

            // Officers
            $table->unsignedBigInteger('filed_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tax_type', 'fiscal_year', 'period_month'], 'tax_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_calendar');
    }
};
