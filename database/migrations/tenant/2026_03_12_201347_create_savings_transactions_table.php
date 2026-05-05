<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('transaction_ref')->unique();
            $table->foreignId('account_id')->constrained('savings_accounts');
            $table->foreignId('member_id')->constrained('members');

            // ─── Transaction Details ─────────────────────
            $table->string('transaction_type', 30);                           // deposit, withdrawal, transfer_in, transfer_out, interest_credit, penalty_debit, reversal
            $table->decimal('amount', 15, 2);
            $table->decimal('running_balance', 15, 2);                        // Account balance after this transaction
            $table->text('description')->nullable();

            // ─── Channel ────────────────────────────────
            $table->string('channel', 30)->default('branch');                 // branch, mobile, ussd, agent, eft, payroll, standing_order, cheque
            $table->string('reference_number')->nullable();                   // External reference (e.g., mobile money ID)

            // ─── Transfer Details ────────────────────────
            $table->foreignId('counterpart_account_id')->nullable()->constrained('savings_accounts'); // For transfers

            // ─── Reversal ───────────────────────────────
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversal_of')->nullable()->constrained('savings_transactions');

            // ─── Audit ──────────────────────────────────
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->date('value_date');
            $table->timestamp('posted_at');

            $table->timestamps();

            $table->index('transaction_type');
            $table->index('channel');
            $table->index('value_date');
            $table->index('account_id');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
