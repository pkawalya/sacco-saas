<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_chains', function (Blueprint $table) {
            $table->id();

            // Chain definition (FR-AN-032)
            $table->string('alert_type', 50)->index()
                ->comment('Event type this chain handles');
            $table->unsignedTinyInteger('tier')->default(1)
                ->comment('Escalation level 1-5');
            $table->string('recipient_role', 50)
                ->comment('officer | supervisor | manager | ceo | cfo');

            // Timing
            $table->unsignedSmallInteger('escalate_after_minutes')->default(60)
                ->comment('Minutes to wait before escalating to next tier');

            // Action
            $table->string('notification_channel', 20)->default('email')
                ->comment('sms | email | push | dashboard');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['alert_type', 'tier'], 'chain_type_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_chains');
    }
};
