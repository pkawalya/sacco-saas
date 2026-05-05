<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('email_verified_at');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
        });

        // Auto-approve all existing users who have the super_admin role
        $superAdminUserIds = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'super_admin')
            ->where('model_has_roles.model_type', 'App\\Models\\Central\\User')
            ->pluck('model_has_roles.model_id');

        if ($superAdminUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $superAdminUserIds)
                ->update([
                    'is_approved' => true,
                    'approved_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'approved_at', 'approved_by']);
        });
    }
};
