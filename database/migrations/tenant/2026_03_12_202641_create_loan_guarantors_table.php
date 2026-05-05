<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_guarantors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('guarantor_member_id')->constrained('members');
            $table->foreignId('guaranteed_savings_account_id')->nullable()->constrained('savings_accounts');

            // ─── Guarantee Terms ─────────────────────────
            $table->decimal('guaranteed_amount', 15, 2);                 // Amount locked in savings
            $table->decimal('original_savings_balance', 15, 2)->nullable(); // Balance before lock
            $table->decimal('locked_amount', 15, 2)->default(0);         // Currently locked

            // ─── Status ─────────────────────────────────
            $table->string('status', 30)->default('active');             // active, released, substituted
            $table->date('released_date')->nullable();
            $table->text('release_reason')->nullable();

            // ─── Substitution ────────────────────────────
            $table->foreignId('substituted_by_guarantor_id')->nullable()->constrained('loan_guarantors');
            $table->timestamp('substituted_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('loan_id');
            $table->index('guarantor_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_guarantors');
    }
};
