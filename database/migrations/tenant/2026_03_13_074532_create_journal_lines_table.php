<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');

            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            $table->string('narration')->nullable();
            $table->string('cost_centre_code', 20)->nullable();
            $table->string('branch_code', 20)->nullable();

            // ─── Reference back to source ────────────────
            $table->string('source_reference', 100)->nullable();

            $table->timestamps();

            $table->index('account_id');
            $table->index('journal_entry_id');
            $table->index('cost_centre_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
