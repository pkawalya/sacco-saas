<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aml_alerts', function (Blueprint $table) {
            $table->id();

            // Identity (FR-RC-010)
            $table->string('alert_id', 30)->unique();
            $table->string('rule_triggered', 100)->index()
                ->comment('threshold_breach | rapid_transactions | structuring | pep_match | sanctions_match | unusual_pattern');

            // Member/transaction
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('member_name', 200);
            $table->string('account_number', 30)->nullable();
            $table->decimal('transaction_amount', 18, 2)->nullable();
            $table->decimal('cumulative_amount', 18, 2)->nullable()
                ->comment('Rolling sum triggering the alert');
            $table->string('transaction_reference', 50)->nullable();

            // Severity & classification
            $table->string('severity', 10)->default('medium')->index()
                ->comment('low | medium | high | critical');
            $table->boolean('is_escalated')->default(false);

            // Status (FR-RC-011)
            $table->string('status', 20)->default('new')->index()
                ->comment('new | under_review | escalated | cleared | str_filed');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Risk score
            $table->unsignedSmallInteger('risk_score')->default(0)
                ->comment('0-100 computed risk score');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_alerts');
    }
};
