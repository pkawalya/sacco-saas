<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')->constrained('lending_groups')->cascadeOnDelete();
            $table->unsignedBigInteger('member_id')->index();
            $table->string('member_name', 200);

            // Role
            $table->string('role', 20)->default('member')
                ->comment('chairperson | secretary | treasurer | member');

            // Performance
            $table->decimal('personal_repayment_rate', 5, 2)->default(100);
            $table->unsignedSmallInteger('loans_taken')->default(0);
            $table->unsignedSmallInteger('loans_defaulted')->default(0);
            $table->decimal('total_borrowed', 18, 2)->default(0);
            $table->decimal('total_repaid', 18, 2)->default(0);

            $table->string('status', 20)->default('active')
                ->comment('active | exited | suspended');
            $table->date('joined_at')->nullable();

            $table->timestamps();

            $table->unique(['group_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
