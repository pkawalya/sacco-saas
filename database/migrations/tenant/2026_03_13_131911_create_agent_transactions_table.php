<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_ref', 30)->unique();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            $table->string('transaction_type', 20)->index()
                ->comment('deposit | withdrawal | float_top_up | float_deduction | commission');

            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('member_name', 200)->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->decimal('float_before', 18, 2)->nullable();
            $table->decimal('float_after', 18, 2)->nullable();

            $table->string('status', 20)->default('completed')
                ->comment('completed | reversed | pending');
            $table->text('narration')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_transactions');
    }
};
