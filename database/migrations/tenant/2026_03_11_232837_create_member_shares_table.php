<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->integer('shares_held')->default(0);
            $table->decimal('par_value', 12, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('percentage_of_total', 8, 4)->default(0);
            $table->timestamps();

            $table->unique('member_id');
        });

        Schema::create('share_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('to_member_id')->constrained('members')->cascadeOnDelete();
            $table->integer('shares_transferred');
            $table->decimal('transfer_price', 15, 2);
            $table->string('status', 30)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_transfers');
        Schema::dropIfExists('member_shares');
    }
};
