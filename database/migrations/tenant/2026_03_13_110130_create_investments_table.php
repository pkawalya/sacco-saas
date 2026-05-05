<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('investment_code', 30)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();

            // Classification (FR-RE-030–034)
            $table->string('investment_type', 50)->index()
                ->comment('treasury_bill | treasury_bond | fixed_deposit | equity | property | money_market | other');
            $table->string('counterparty', 200)->nullable()
                ->comment('Bank, broker, or institution holding the investment');

            // GL linkage
            $table->foreignId('gl_account_id')
                ->constrained('chart_of_accounts')
                ->restrictOnDelete();
            $table->foreignId('income_account_id')
                ->nullable()
                ->constrained('chart_of_accounts')
                ->nullOnDelete()
                ->comment('GL account for investment income');

            // Financials
            $table->decimal('face_value', 18, 2)->comment('Original/par value');
            $table->decimal('purchase_price', 18, 2);
            $table->decimal('current_value', 18, 2)->comment('Mark-to-market or amortised cost');
            $table->decimal('accrued_income', 18, 2)->default(0);
            $table->string('currency', 3)->default('UGX');

            // Rate & return
            $table->decimal('interest_rate', 8, 4)->default(0)
                ->comment('Annual yield / coupon rate %');
            $table->decimal('expected_return', 18, 2)->default(0)
                ->comment('Projected total return at maturity');

            // Dates
            $table->date('purchase_date');
            $table->date('maturity_date')->nullable();
            $table->date('last_valuation_date')->nullable();

            // Status
            $table->string('status', 20)->default('active')->index()
                ->comment('active | matured | sold | written_off');

            // Metadata
            $table->string('reference_number', 50)->nullable()
                ->comment('Certificate/bond number from institution');
            $table->json('metadata')->nullable()
                ->comment('Type-specific fields: coupon_frequency, lot_size, property_address, etc.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
