<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_letters', function (Blueprint $table) {
            $table->id();

            // Linkage
            $table->foreignId('worklist_id')
                ->constrained('collections_worklist')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('loan_id')->index();
            $table->string('loan_number', 30)->index();

            // Letter details (FR-CE-013)
            $table->string('letter_type', 30)->index()
                ->comment('reminder | first_demand | final_demand | legal_notice | guarantor_notice');
            $table->string('reference_number', 50)->unique();
            $table->text('content')->nullable();

            // Recipient
            $table->string('recipient_name', 200);
            $table->string('recipient_address', 500)->nullable();
            $table->string('recipient_type', 20)->default('borrower')
                ->comment('borrower | guarantor');

            // Delivery
            $table->string('delivery_method', 20)->default('print')
                ->comment('print | email | sms | courier');
            $table->date('sent_date')->nullable();
            $table->date('acknowledged_date')->nullable();

            // Status
            $table->string('status', 20)->default('draft')->index()
                ->comment('draft | sent | acknowledged | returned');

            // Officer
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_letters');
    }
};
