<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();

            $table->string('base_currency', 3)->default('UGX');
            $table->string('quote_currency', 3)->index();
            $table->decimal('buy_rate', 18, 6);
            $table->decimal('sell_rate', 18, 6);
            $table->decimal('mid_rate', 18, 6);
            $table->decimal('spread', 18, 6)->nullable();

            $table->string('source', 30)->default('manual')
                ->comment('manual | bou | reuters');
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency', 'effective_date'], 'fx_rate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_rates');
    }
};
