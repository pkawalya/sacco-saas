<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centres', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            // 4-level hierarchy (FR-CC-001): Division > Department > Branch > Unit
            $table->unsignedTinyInteger('level')->index()
                ->comment('1=Division, 2=Department, 3=Branch, 4=Unit');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('cost_centres')
                ->restrictOnDelete();

            // Manager
            $table->string('manager_name', 150)->nullable();
            $table->unsignedBigInteger('manager_user_id')->nullable();

            // Status & historical data preservation (FR-CC-002)
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->text('deactivation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centres');
    }
};
