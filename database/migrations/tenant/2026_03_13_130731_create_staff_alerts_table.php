<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_alerts', function (Blueprint $table) {
            $table->id();

            // Identity (FR-AN-020)
            $table->string('alert_id', 30)->unique();
            $table->string('event_type', 50)->index()
                ->comment('threshold_exceeded | loan_overdue | fraud_alert | system_error | etc.');

            // Content
            $table->string('title', 200);
            $table->text('message');
            $table->string('severity', 10)->default('info')->index()
                ->comment('info | warning | critical');

            // Recipient
            $table->unsignedBigInteger('recipient_id')->index();
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_role', 50)->nullable()
                ->comment('officer | supervisor | manager | ceo | cfo');

            // Acknowledgement (FR-AN-021)
            $table->string('status', 20)->default('unread')->index()
                ->comment('unread | read | acknowledged | escalated');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            // Escalation (FR-AN-022)
            $table->boolean('is_escalated')->default(false);
            $table->unsignedTinyInteger('escalation_tier')->default(1);
            $table->timestamp('escalated_at')->nullable();
            $table->unsignedBigInteger('escalated_to')->nullable();

            // Metadata
            $table->json('context')->nullable()
                ->comment('Additional context data for the alert');
            $table->string('source_module', 50)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_alerts');
    }
};
