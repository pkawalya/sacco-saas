<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();

            $table->string('card_number', 20)->unique();
            $table->string('masked_pan', 20)
                ->comment('XXXX-XXXX-XXXX-1234');
            $table->unsignedBigInteger('member_id')->index();
            $table->string('cardholder_name', 200);

            $table->string('card_type', 20)->index()
                ->comment('debit | prepaid | virtual');
            $table->string('card_scheme', 10)->default('visa')
                ->comment('visa | mastercard');

            // Limits
            $table->decimal('daily_limit', 18, 2)->default(2000000);
            $table->decimal('monthly_limit', 18, 2)->default(20000000);
            $table->decimal('pos_limit', 18, 2)->default(5000000);
            $table->decimal('atm_limit', 18, 2)->default(1000000);

            // Linked account
            $table->string('linked_account', 20)->nullable();

            $table->date('expiry_date');
            $table->string('status', 20)->default('active')
                ->comment('active | blocked | expired | cancelled');
            $table->timestamp('blocked_at')->nullable();
            $table->string('block_reason', 200)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
