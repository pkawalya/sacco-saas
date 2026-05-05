<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('journal_number')->unique();
            $table->string('journal_type', 30)->default('manual'); // system, manual, auto_reversal
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('description');

            // ─── Reference ──────────────────────────────
            $table->string('source_module', 50)->nullable();       // savings, loans, etc.
            $table->string('source_reference', 100)->nullable();   // TXN-xxx, RCP-xxx
            $table->unsignedBigInteger('source_id')->nullable();

            // ─── Period ─────────────────────────────────
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods');

            // ─── Totals ─────────────────────────────────
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->string('currency_code', 3)->default('UGX');

            // ─── Auto-Reversal ──────────────────────────
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries');
            $table->date('auto_reverse_date')->nullable();

            // ─── Status & Approval ──────────────────────
            $table->string('status', 20)->default('draft');        // draft, posted, reversed, void
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // Dual auth for manual journals
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('journal_type');
            $table->index('transaction_date');
            $table->index('status');
            $table->index('source_module');
            $table->index('period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
