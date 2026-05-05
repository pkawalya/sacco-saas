<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('template_code', 50)->unique()->comment('Unique code e.g. LOAN_APPROVED, DEPOSIT_CONFIRMED');
            $table->string('name', 150)->comment('Human-readable template name');

            // Event binding
            $table->string('event_type', 100)->index()->comment('Event this template fires on e.g. loan.approved, deposit.confirmed');
            $table->string('module', 80)->nullable()->index()->comment('Module key from config/modules.php');

            // Channel & content
            $table->string('channel', 20)->default('sms')->comment('sms | email | push | in_app');
            $table->string('subject', 255)->nullable()->comment('Email subject line (merge fields supported)');
            $table->text('body')->comment('Template body with merge fields e.g. {member_name}, {amount}');

            // Merge fields metadata
            $table->json('merge_fields')->nullable()->comment('Available merge fields: [{key, label, sample}]');

            // Controls
            $table->string('priority', 10)->default('normal')->comment('low | normal | high | critical');
            $table->boolean('is_mandatory')->default(false)->comment('Cannot be opted-out by member');
            $table->boolean('is_active')->default(true);

            // Data masking (FR-AN-010)
            $table->boolean('mask_sensitive_data')->default(true)->comment('Mask account numbers, amounts in notification');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
