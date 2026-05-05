<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            // Identity (FR-CH-030)
            $table->string('agent_code', 20)->unique();
            $table->string('agent_name', 200);
            $table->string('business_name', 200)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('branch_code', 20)->nullable()->index();

            // Float (FR-CH-030)
            $table->decimal('float_balance', 18, 2)->default(0);
            $table->decimal('float_limit', 18, 2)->default(5000000)
                ->comment('Maximum float allowed');
            $table->decimal('daily_transaction_limit', 18, 2)->default(20000000);

            // Commission (FR-CH-031)
            $table->decimal('commission_rate', 5, 2)->default(0.50)
                ->comment('Commission % per transaction');
            $table->decimal('total_commission_earned', 18, 2)->default(0);

            // Status
            $table->string('status', 20)->default('active')->index()
                ->comment('active | suspended | deactivated');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
