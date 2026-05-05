<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_collateral', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('member_id')->constrained('members');

            // ─── Asset ──────────────────────────────────
            $table->string('asset_type', 50);                            // land_title, vehicle, building, equipment, stock, livestock
            $table->string('asset_description', 200);
            $table->string('asset_identifier', 100)->nullable();         // Title number, chassis number, etc.
            $table->string('location')->nullable();

            // ─── Valuation ──────────────────────────────
            $table->decimal('estimated_value', 15, 2);
            $table->decimal('forced_sale_value', 15, 2)->nullable();
            $table->date('valuation_date')->nullable();
            $table->string('valuer_name')->nullable();

            // ─── Insurance ──────────────────────────────
            $table->boolean('is_insured')->default(false);
            $table->string('insurance_company')->nullable();
            $table->string('policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->decimal('insurance_cover_amount', 15, 2)->nullable();

            // ─── Status ─────────────────────────────────
            $table->string('status', 30)->default('active');             // active, released, foreclosed

            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_type');
            $table->index('status');
            $table->index('insurance_expiry_date');
            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_collateral');
    }
};
