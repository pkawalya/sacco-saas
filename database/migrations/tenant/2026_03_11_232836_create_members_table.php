<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            // ─── Identity ───────────────────────────────
            $table->string('member_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender', 20);
            $table->string('nationality')->default('Ugandan');
            $table->string('national_id_type', 50)->default('national_id');
            $table->string('national_id_number')->unique();
            $table->string('photo_path')->nullable();

            // ─── Contact ────────────────────────────────
            $table->string('physical_address')->nullable();
            $table->string('village')->nullable();
            $table->string('cell')->nullable();
            $table->string('district')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('primary_phone');
            $table->string('secondary_phone')->nullable();
            $table->string('email')->nullable();

            // ─── Employment ─────────────────────────────
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('monthly_income_range', 50)->nullable();

            // ─── Next of Kin ────────────────────────────
            $table->string('nok_name')->nullable();
            $table->string('nok_relationship')->nullable();
            $table->string('nok_contact')->nullable();

            // ─── Classification ─────────────────────────
            $table->string('member_category')->default('individual');
            $table->string('referral_source')->nullable();
            $table->unsignedTinyInteger('kyc_score')->default(0);
            $table->unsignedTinyInteger('kyc_threshold')->default(70);
            $table->string('branch_code')->nullable();

            // ─── Lifecycle ──────────────────────────────
            $table->string('status', 30)->default('applicant');

            // ─── Audit ──────────────────────────────────
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dormant_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // ─── Indexes ────────────────────────────────
            $table->index('status');
            $table->index('branch_code');
            $table->index('member_category');
            $table->index('primary_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
