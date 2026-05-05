<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teller_transactions', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('transaction_ref', 30)->unique();
            $table->foreignId('shift_id')
                ->constrained('teller_shifts')
                ->cascadeOnDelete();

            // Type
            $table->string('transaction_type', 20)->index()
                ->comment('deposit | withdrawal | transfer_in | transfer_out | reversal');

            // Parties
            $table->unsignedBigInteger('teller_id')->index();
            $table->string('teller_name', 150);
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('member_name', 200)->nullable();
            $table->string('account_number', 30)->nullable();

            // Amount
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('UGX');

            // Transfer (FR-CH-003)
            $table->unsignedBigInteger('counterpart_teller_id')->nullable()
                ->comment('For inter-teller transfers');
            $table->string('counterpart_branch', 20)->nullable();

            // Verification
            $table->string('status', 20)->default('completed')->index()
                ->comment('completed | reversed | pending');
            $table->boolean('requires_approval')->default(false)
                ->comment('True when exceeds limit');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('narration')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teller_transactions');
    }
};
