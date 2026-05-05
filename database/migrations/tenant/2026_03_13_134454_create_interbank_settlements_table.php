<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interbank_settlements', function (Blueprint $table) {
            $table->id();

            $table->string('settlement_ref', 30)->unique();
            $table->string('settlement_type', 20)->index()
                ->comment('eft | rtgs | mobile_money | cheque_clearing');

            // Parties
            $table->string('originating_bank', 100);
            $table->string('originating_account', 30);
            $table->string('receiving_bank', 100);
            $table->string('receiving_account', 30);

            // Amount
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('UGX');
            $table->decimal('fee', 18, 2)->default(0);

            // Timing
            $table->date('value_date');
            $table->timestamp('initiated_at');
            $table->timestamp('settled_at')->nullable();

            // Status
            $table->string('status', 20)->default('pending')
                ->comment('pending | processing | settled | failed | reversed');
            $table->text('failure_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interbank_settlements');
    }
};
