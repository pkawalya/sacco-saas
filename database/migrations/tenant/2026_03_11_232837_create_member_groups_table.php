<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_name');
            $table->string('group_code')->unique();
            $table->string('branch_code')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('member_group_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('member');
            $table->timestamps();

            $table->unique(['member_group_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_group_member');
        Schema::dropIfExists('member_groups');
    }
};
