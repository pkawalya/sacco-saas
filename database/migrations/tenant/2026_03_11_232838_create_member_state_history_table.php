<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_state_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('from_state', 30);
            $table->string('to_state', 30);
            $table->string('reason_code', 50)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->timestamp('transitioned_at');
            $table->timestamps();

            $table->index(['member_id', 'transitioned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_state_history');
    }
};
