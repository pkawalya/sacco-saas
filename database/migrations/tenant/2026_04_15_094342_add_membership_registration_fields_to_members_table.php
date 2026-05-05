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
        Schema::table('members', function (Blueprint $table) {
            // Next of Kin additional fields
            $table->string('nok_gender', 20)->nullable()->after('nok_contact');
            $table->string('nok_national_id_number')->nullable()->after('nok_gender');
            $table->string('nok_national_id_document')->nullable()->after('nok_national_id_number');
            $table->string('nok_marital_status', 20)->nullable()->after('nok_national_id_document');

            // Membership Intention
            $table->string('member_intention', 20)->nullable()->after('nok_marital_status');
            $table->decimal('willing_weekly_savings_amount', 15, 2)->nullable()->after('member_intention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'nok_gender',
                'nok_national_id_number',
                'nok_national_id_document',
                'nok_marital_status',
                'member_intention',
                'willing_weekly_savings_amount',
            ]);
        });
    }
};
