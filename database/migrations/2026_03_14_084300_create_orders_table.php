<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->string('organization_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('plan_tier', 30)->comment('starter|growth|enterprise');
            $table->string('billing_cycle', 20)->default('monthly');
            $table->unsignedInteger('member_count')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending|contacted|active|cancelled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
