<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections_activities', function (Blueprint $table) {
            $table->id();

            // Linkage
            $table->foreignId('worklist_id')
                ->constrained('collections_worklist')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('loan_id')->index();
            $table->string('loan_number', 30)->index();

            // Activity (FR-CE-010)
            $table->string('activity_type', 30)->index()
                ->comment('call | visit | sms | email | ptp | letter | legal | note');
            $table->text('description');
            $table->string('outcome', 50)->nullable()
                ->comment('contacted | not_reachable | promise_made | refused | partial_payment | arrangement');

            // Officer
            $table->unsignedBigInteger('officer_id')->nullable();
            $table->string('officer_name', 150)->nullable();

            // Metadata
            $table->string('contact_number', 30)->nullable();
            $table->timestamp('contact_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable()
                ->comment('Call/visit duration');
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections_activities');
    }
};
