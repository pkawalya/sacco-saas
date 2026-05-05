<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('action', 30)->comment('create|update|delete|approve');
            $table->json('payload')->nullable()->comment('Proposed changes');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending|approved|rejected');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index('status');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
