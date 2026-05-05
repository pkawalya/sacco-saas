<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('report_code', 30)->unique();
            $table->string('report_name', 200);
            $table->string('report_type', 30)->index()
                ->comment('financial_statement | custom | regulatory | dashboard');
            $table->string('category', 50)->nullable()
                ->comment('income_statement | balance_sheet | cash_flow | trial_balance | custom');

            $table->json('columns')
                ->comment('Column definitions [{name, source, format}]');
            $table->json('filters')->nullable()
                ->comment('Available filter parameters');
            $table->json('parameters')->nullable()
                ->comment('Default parameter values');

            $table->string('output_format', 20)->default('table')
                ->comment('table | chart | pivot | pdf');
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_cron', 50)->nullable();
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
    }
};
