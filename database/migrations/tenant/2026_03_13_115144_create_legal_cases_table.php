<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('case_ref', 50)->unique();

            // Linkage
            $table->foreignId('worklist_id')
                ->constrained('collections_worklist')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('loan_id')->index();
            $table->string('loan_number', 30)->index();

            // Court details
            $table->string('court', 200)->nullable();
            $table->string('case_number', 100)->nullable()
                ->comment('Court-assigned case number');
            $table->date('filing_date');
            $table->date('next_hearing_date')->nullable();

            // Amounts
            $table->decimal('claim_amount', 18, 2);
            $table->decimal('legal_costs', 18, 2)->default(0);
            $table->decimal('recovered_amount', 18, 2)->default(0);

            // Status
            $table->string('status', 20)->default('filed')->index()
                ->comment('filed | hearing | judgment | execution | settled | closed');

            // Advocate
            $table->string('advocate_name', 200)->nullable();
            $table->string('advocate_contact', 100)->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('filed_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
