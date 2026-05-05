<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lending_groups', function (Blueprint $table) {
            $table->id();

            $table->string('group_code', 20)->unique();
            $table->string('group_name', 200);
            $table->string('branch_code', 20)->nullable()->index();

            // Group config
            $table->unsignedSmallInteger('max_members')->default(30);
            $table->unsignedSmallInteger('min_members')->default(5);
            $table->string('liability_type', 20)->default('joint')
                ->comment('joint | individual | hybrid');
            $table->decimal('group_savings_balance', 18, 2)->default(0);

            // Performance
            $table->decimal('repayment_rate', 5, 2)->default(100)
                ->comment('Group repayment rate %');
            $table->unsignedSmallInteger('cycle_number')->default(1)
                ->comment('Current lending cycle');
            $table->decimal('max_loan_per_member', 18, 2)->default(5000000);

            $table->string('status', 20)->default('active')
                ->comment('active | probation | suspended | graduated');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lending_groups');
    }
};
