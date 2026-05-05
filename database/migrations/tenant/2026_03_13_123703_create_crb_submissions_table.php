<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crb_submissions', function (Blueprint $table) {
            $table->id();

            // Identity (FR-RC-020)
            $table->string('submission_ref', 50)->unique();

            // Period
            $table->date('submission_date');
            $table->string('period', 20)
                ->comment('monthly | quarterly');
            $table->date('period_start');
            $table->date('period_end');

            // Data
            $table->unsignedInteger('record_count')->default(0);
            $table->unsignedInteger('positive_records')->default(0)
                ->comment('Performing accounts');
            $table->unsignedInteger('negative_records')->default(0)
                ->comment('Non-performing accounts');

            // Status
            $table->string('status', 20)->default('pending')->index()
                ->comment('pending | submitted | accepted | rejected');
            $table->text('rejection_reason')->nullable();

            // CRB details
            $table->string('crb_name', 100)->nullable()
                ->comment('TransUnion, Metropol, etc.');
            $table->string('crb_reference', 100)->nullable();

            // Officers
            $table->unsignedBigInteger('submitted_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crb_submissions');
    }
};
