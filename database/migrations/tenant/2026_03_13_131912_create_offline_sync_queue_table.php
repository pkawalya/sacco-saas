<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_queue', function (Blueprint $table) {
            $table->id();

            // Source (FR-CH-040)
            $table->string('branch_code', 20)->index();
            $table->string('device_id', 50)->nullable()
                ->comment('Client device identifier');

            // Batch info
            $table->unsignedInteger('transaction_count')->default(0);
            $table->json('payload')
                ->comment('Serialized offline transactions');

            // Sync status (FR-CH-041–042)
            $table->string('sync_status', 20)->default('pending')->index()
                ->comment('pending | syncing | synced | conflict | failed');
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('synced_at')->nullable();

            // Conflict resolution (FR-CH-042)
            $table->json('conflicts')->nullable()
                ->comment('Conflict details if any');
            $table->string('resolution_strategy', 20)->nullable()
                ->comment('server_wins | client_wins | manual');
            $table->unsignedBigInteger('resolved_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_queue');
    }
};
