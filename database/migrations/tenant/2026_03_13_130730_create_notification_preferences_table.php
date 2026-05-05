<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();

            // Member linkage (FR-AN-012)
            $table->unsignedBigInteger('member_id')->index();

            // Preferences per event type
            $table->string('event_type', 50)->index()
                ->comment('loan_disbursement | payment_received | account_alert | etc.');
            $table->string('channel', 20)->default('sms')
                ->comment('sms | email | push | whatsapp');
            $table->boolean('is_enabled')->default(true);

            // Language preference (FR-AN-011)
            $table->string('language', 10)->default('en')
                ->comment('en | lg | sw | etc.');

            $table->timestamps();

            $table->unique(['member_id', 'event_type', 'channel'], 'pref_member_event_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
