<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('account_number')->unique();
            $table->foreignId('member_id')->constrained('members');
            $table->foreignId('product_id')->constrained('savings_products');

            // ─── Balance ────────────────────────────────
            $table->decimal('ledger_balance', 15, 2)->default(0.00);         // Total funds
            $table->decimal('available_balance', 15, 2)->default(0.00);      // Ledger minus holds
            $table->decimal('held_amount', 15, 2)->default(0.00);            // Locked (e.g., guarantor)
            $table->decimal('accrued_interest', 15, 2)->default(0.00);       // Unposted interest

            // ─── Joint Account ──────────────────────────
            $table->boolean('is_joint')->default(false);
            $table->string('mandate_type', 20)->nullable();                   // AOS (any one to sign), BAS (both any sign)
            $table->json('joint_member_ids')->nullable();

            // ─── Status & Lifecycle ──────────────────────
            $table->string('status', 30)->default('active');                  // active, dormant, closed, suspended

            // ─── Account Metadata ────────────────────────
            $table->string('branch_code')->nullable();
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->date('opened_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->text('closure_reason')->nullable();
            $table->date('last_transaction_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('branch_code');
            $table->index('member_id');
            $table->index('last_transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
