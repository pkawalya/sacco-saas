<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crb_inquiries', function (Blueprint $table) {
            $table->id();

            $table->string('inquiry_ref', 30)->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->string('member_name', 200);
            $table->string('national_id', 30)->nullable();

            // Inquiry
            $table->string('crb_name', 50)->default('TransUnion')
                ->comment('TransUnion | Metropol');
            $table->string('inquiry_type', 20)->default('credit_score')
                ->comment('credit_score | full_report');
            $table->timestamp('inquiry_date');

            // Result
            $table->unsignedSmallInteger('credit_score')->nullable();
            $table->string('risk_grade', 5)->nullable()
                ->comment('AA | A | B | C | D | HR');
            $table->json('report_data')->nullable();
            $table->decimal('total_exposure', 18, 2)->nullable();
            $table->unsignedSmallInteger('active_facilities')->nullable();
            $table->unsignedSmallInteger('npls')->nullable();

            // Status
            $table->string('status', 20)->default('pending')
                ->comment('pending | completed | failed | expired');
            $table->text('failure_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crb_inquiries');
    }
};
