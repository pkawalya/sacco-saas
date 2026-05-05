<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained('loans');
            $table->unsignedTinyInteger('approval_level');               // 1, 2, 3
            $table->unsignedBigInteger('approver_id');
            $table->string('role', 50)->nullable();                      // loan_officer, branch_manager, credit_committee, board
            $table->string('decision', 20);                              // approved, declined, queried, deferred
            $table->decimal('amount_approved', 15, 2)->nullable();       // May differ from requested
            $table->text('notes')->nullable();
            $table->timestamp('decided_at');

            $table->timestamps();

            $table->index(['loan_id', 'approval_level']);
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_approvals');
    }
};
