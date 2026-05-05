<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_sources', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('source_code', 30)->unique();
            $table->string('source_name', 150);
            $table->text('description')->nullable();

            // Classification
            $table->string('revenue_type', 50)->index()
                ->comment('interest | fee | commission | penalty | investment | other');
            $table->string('recognition_basis', 30)->default('accrual')
                ->comment('accrual | cash | hybrid');

            // GL linkage
            $table->foreignId('gl_account_id')
                ->constrained('chart_of_accounts')
                ->restrictOnDelete();
            $table->foreignId('wht_account_id')
                ->nullable()
                ->constrained('chart_of_accounts')
                ->nullOnDelete()
                ->comment('GL account for WHT payable');

            // WHT (FR-RE-010–014)
            $table->decimal('wht_rate', 5, 2)->default(0.00)
                ->comment('Withholding tax rate %');
            $table->boolean('wht_applicable')->default(false);

            // Controls
            $table->boolean('is_active')->default(true);
            $table->string('frequency', 20)->default('one_time')
                ->comment('one_time | daily | monthly | quarterly | annually');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_sources');
    }
};
