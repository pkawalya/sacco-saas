<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atm_terminals', function (Blueprint $table) {
            $table->id();

            $table->string('terminal_id', 20)->unique();
            $table->string('terminal_name', 100);
            $table->string('location', 200);
            $table->string('branch_code', 20)->nullable()->index();

            // Cash
            $table->decimal('current_cash', 18, 2)->default(0);
            $table->decimal('max_cash', 18, 2)->default(50000000);
            $table->decimal('min_cash_alert', 18, 2)->default(5000000);
            $table->unsignedInteger('total_transactions_today')->default(0);

            // Status
            $table->string('status', 20)->default('online')
                ->comment('online | offline | maintenance | out_of_cash');
            $table->timestamp('last_replenished_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atm_terminals');
    }
};
