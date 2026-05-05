<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loan_collateral', function (Blueprint $table) {
            $table->json('documents')->nullable()->after('insurance_cover_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_collateral', function (Blueprint $table) {
            $table->dropColumn('documents');
        });
    }
};
