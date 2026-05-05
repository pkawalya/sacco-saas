<?php

namespace Database\Seeders;

use App\Models\Central\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@sacco.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_approved' => true,
                'approved_at' => now(),
                'must_change_password' => false,
            ]
        );

        $adminRole = Role::where('name', 'super_admin')->first();
        if ($adminRole && ! $admin->hasRole('super_admin')) {
            $admin->assignRole($adminRole);
        }

        // 2. Regular User (Tenant Owner)
        $user = User::firstOrCreate(
            ['email' => 'user@sacco.test'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_approved' => true,
                'approved_at' => now(),
            ]
        );

        $userRole = Role::where('name', 'user')->first();
        if ($userRole && ! $user->hasRole('user')) {
            $user->assignRole($userRole);
        }
    }
}
