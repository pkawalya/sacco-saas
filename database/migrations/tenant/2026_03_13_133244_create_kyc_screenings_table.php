<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_screenings', function (Blueprint $table) {
            $table->id();

            $table->string('screening_ref', 30)->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->string('member_name', 200);

            // Screening type
            $table->string('screening_type', 20)->index()
                ->comment('pep | sanctions | adverse_media | id_verification');
            $table->unsignedTinyInteger('kyc_tier')->default(1)
                ->comment('1=basic, 2=enhanced, 3=full');

            // Result
            $table->string('result', 20)->default('pending')
                ->comment('pending | clear | match | review_needed | failed');
            $table->decimal('match_score', 5, 2)->nullable()
                ->comment('0-100 confidence score');
            $table->json('match_details')->nullable();

            // Source
            $table->string('data_source', 50)->nullable()
                ->comment('nira | iprs | sanctions_list | pep_database');
            $table->string('verification_id', 100)->nullable()
                ->comment('External reference from verification API');

            // Review
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_screenings');
    }
};
