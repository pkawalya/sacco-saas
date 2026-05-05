<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulatory_returns', function (Blueprint $table) {
            $table->id();

            // Identity (FR-RC-001)
            $table->string('return_code', 30)->unique();
            $table->string('return_name', 200);
            $table->string('return_type', 30)->index()
                ->comment('prudential | statistical | aml | tax | crb');

            // Period
            $table->string('period', 20)
                ->comment('monthly | quarterly | annual');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('period_number')->nullable()
                ->comment('Month 1-12 or Quarter 1-4');
            $table->date('period_start');
            $table->date('period_end');

            // Deadline (FR-RC-002)
            $table->date('due_date');
            $table->unsignedSmallInteger('reminder_days_before')->default(7);

            // Filing (FR-RC-003)
            $table->string('status', 20)->default('pending')->index()
                ->comment('pending | in_progress | submitted | accepted | rejected | overdue');
            $table->date('filed_date')->nullable();
            $table->string('filing_reference', 100)->nullable();
            $table->unsignedBigInteger('filed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            // Content
            $table->json('return_data')->nullable()
                ->comment('Generated return data in JSON');
            $table->text('notes')->nullable();
            $table->string('attachment_path', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_returns');
    }
};
