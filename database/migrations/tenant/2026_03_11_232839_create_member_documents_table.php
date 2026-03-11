<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('file_path');
            $table->date('upload_date');
            $table->date('expiry_date')->nullable();
            $table->string('verification_status', 30)->default('pending');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_documents');
    }
};
