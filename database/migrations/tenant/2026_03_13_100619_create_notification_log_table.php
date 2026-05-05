<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();

            // Template reference
            $table->foreignId('notification_template_id')
                ->nullable()
                ->constrained('notification_templates')
                ->nullOnDelete();

            // Recipient
            $table->string('recipient_type', 20)->default('member')->comment('member | staff | external');
            $table->unsignedBigInteger('recipient_id')->nullable()->index()->comment('Member/User ID');
            $table->string('recipient_identifier', 255)->comment('Phone number, email, or device token');

            // Delivery
            $table->string('channel', 20)->index()->comment('sms | email | push | in_app');
            $table->string('event_type', 100)->index();
            $table->string('priority', 10)->default('normal');

            // Content snapshot (for audit)
            $table->string('subject', 255)->nullable();
            $table->text('rendered_body')->comment('Final rendered content sent to recipient');

            // Status tracking (FR-AN-001 failover)
            $table->string('status', 20)->default('pending')->index()
                ->comment('pending | queued | sent | delivered | failed | bounced');
            $table->string('failover_channel', 20)->nullable()->comment('Channel used after primary failed');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            // Provider response
            $table->string('provider', 50)->nullable()->comment('SMS gateway or email provider name');
            $table->string('external_id', 255)->nullable()->comment('Provider message ID for tracking');
            $table->text('error_message')->nullable();

            // Source reference
            $table->string('source_module', 80)->nullable();
            $table->string('source_reference', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // Timestamps (FR-AN-041: immutable audit)
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Indexes for reporting & searching
            $table->index(['event_type', 'status']);
            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_log');
    }
};
