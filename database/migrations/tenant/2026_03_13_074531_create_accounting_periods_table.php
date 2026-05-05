<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();

            $table->string('period_name', 50);                // e.g. "January 2026", "FY2026-Q1"
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');              // 1-12
            $table->date('start_date');
            $table->date('end_date');

            // ─── Status ─────────────────────────────────
            $table->string('status', 20)->default('open');     // open, closed, locked
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->text('close_notes')->nullable();

            $table->timestamps();

            $table->unique(['year', 'month']);
            $table->index('status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
