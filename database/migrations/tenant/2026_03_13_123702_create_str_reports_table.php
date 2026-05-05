<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('str_reports', function (Blueprint $table) {
            $table->id();

            // Identity (FR-RC-012-013)
            $table->string('str_reference', 50)->unique();

            // Linkage
            $table->foreignId('aml_alert_id')
                ->nullable()
                ->constrained('aml_alerts')
                ->nullOnDelete();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('member_name', 200);

            // Transaction details
            $table->decimal('amount', 18, 2);
            $table->string('transaction_type', 50)->nullable()
                ->comment('deposit | withdrawal | transfer | loan_disbursement');
            $table->text('suspicious_activity_description');

            // Report type
            $table->string('report_type', 10)->default('str')->index()
                ->comment('str | ctr');

            // Status
            $table->string('status', 20)->default('draft')->index()
                ->comment('draft | submitted | acknowledged | returned');
            $table->date('filed_date')->nullable();
            $table->string('fia_reference', 100)->nullable()
                ->comment('FIA (Financial Intelligence Authority) reference');

            // Officers
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('str_reports');
    }
};
