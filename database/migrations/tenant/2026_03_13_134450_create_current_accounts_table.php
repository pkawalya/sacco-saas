<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('current_accounts', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('account_number', 20)->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->string('account_holder', 200);
            $table->string('account_type', 20)->default('individual')
                ->comment('individual | business | corporate');

            // Balances
            $table->decimal('ledger_balance', 18, 2)->default(0);
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('overdraft_limit', 18, 2)->default(0);
            $table->decimal('minimum_balance', 18, 2)->default(50000);

            // Features
            $table->boolean('cheque_book_issued')->default(false);
            $table->boolean('debit_card_linked')->default(false);
            $table->boolean('internet_banking')->default(false);
            $table->boolean('mobile_banking')->default(false);

            // Charges
            $table->decimal('monthly_fee', 18, 2)->default(10000);
            $table->decimal('transaction_fee', 18, 2)->default(500);

            // Deposit insurance
            $table->boolean('deposit_insured')->default(true);
            $table->decimal('insured_amount', 18, 2)->default(10000000)
                ->comment('DPF coverage limit');

            $table->string('currency', 3)->default('UGX');
            $table->string('status', 20)->default('active')
                ->comment('active | dormant | frozen | closed');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('current_accounts');
    }
};
