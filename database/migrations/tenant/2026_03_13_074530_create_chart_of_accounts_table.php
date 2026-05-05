<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('account_code', 20)->unique();
            $table->string('account_name', 150);
            $table->string('account_type', 30);              // asset, liability, equity, revenue, expense
            $table->string('account_sub_type', 50)->nullable(); // e.g. current_asset, fixed_asset, long_term_liability

            // ─── Hierarchy ──────────────────────────────
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts');
            $table->unsignedTinyInteger('level')->default(1); // 1=category, 2=group, 3=sub-group, 4=detail, 5=leaf
            $table->boolean('is_header')->default(false);     // Header accounts cannot receive postings

            // ─── Behaviour ──────────────────────────────
            $table->string('normal_balance', 10)->default('debit'); // debit or credit
            $table->string('currency_code', 3)->default('UGX');
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_system_account')->default(false);  // System-managed, cannot be deleted

            // ─── Status ─────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('account_type');
            $table->index('parent_id');
            $table->index('level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
