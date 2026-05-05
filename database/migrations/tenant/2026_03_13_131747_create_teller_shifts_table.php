<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teller_shifts', function (Blueprint $table) {
            $table->id();

            // Identity (FR-CH-001)
            $table->string('shift_number', 30)->unique();
            $table->unsignedBigInteger('teller_id')->index();
            $table->string('teller_name', 150);
            $table->string('branch_code', 20)->index();
            $table->string('branch_name', 100)->nullable();

            // Cash (FR-CH-001)
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->nullable();
            $table->decimal('total_deposits', 18, 2)->default(0);
            $table->decimal('total_withdrawals', 18, 2)->default(0);
            $table->decimal('total_transfers_in', 18, 2)->default(0);
            $table->decimal('total_transfers_out', 18, 2)->default(0);

            // Transaction limits (FR-CH-002)
            $table->decimal('deposit_limit', 18, 2)->default(10000000)
                ->comment('Max single deposit allowed');
            $table->decimal('withdrawal_limit', 18, 2)->default(5000000)
                ->comment('Max single withdrawal allowed');
            $table->decimal('daily_cash_limit', 18, 2)->default(50000000)
                ->comment('Max cash handled per shift');

            // Timing
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // Status
            $table->string('status', 20)->default('open')->index()
                ->comment('open | closed | suspended');

            // EOD notes (FR-CH-004)
            $table->text('closing_notes')->nullable();
            $table->decimal('variance', 18, 2)->nullable()
                ->comment('Difference between expected and actual cash');
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teller_shifts');
    }
};
