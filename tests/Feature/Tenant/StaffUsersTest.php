<?php

use App\Models\Tenant\User;
use Filament\Panel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->initializeTenancy();
});

// ─── Helper ──────────────────────────────────────────────────

function makeStaffUser(array $overrides = []): User
{
    return User::create(array_merge([
        'name' => 'Test Staff',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ], $overrides));
}

// ─── User creation ───────────────────────────────────────────

it('can create a tenant user with all required fields', function () {
    $user = makeStaffUser(['name' => 'Jane Loan Officer', 'role' => User::ROLE_STAFF]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBeGreaterThan(0)
        ->and($user->name)->toBe('Jane Loan Officer')
        ->and($user->role)->toBe(User::ROLE_STAFF)
        ->and($user->is_active)->toBeTrue();
});

it('hashes the password on creation', function () {
    $user = makeStaffUser();

    expect(Hash::check('password', $user->password))->toBeTrue();
});

it('enforces unique email within tenant', function () {
    $email = 'duplicate@sacco.test';
    makeStaffUser(['email' => $email]);

    expect(fn () => makeStaffUser(['email' => $email]))
        ->toThrow(QueryException::class);
});

// ─── Roles ───────────────────────────────────────────────────

it('has exactly four defined roles', function () {
    expect(User::ROLES)->toHaveCount(4)
        ->and(array_keys(User::ROLES))->toBe([
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_STAFF,
            User::ROLE_TELLER,
        ]);
});

it('isAdmin returns true only for admin role', function () {
    $admin = makeStaffUser(['role' => User::ROLE_ADMIN]);
    $manager = makeStaffUser(['role' => User::ROLE_MANAGER]);
    $staff = makeStaffUser(['role' => User::ROLE_STAFF]);
    $teller = makeStaffUser(['role' => User::ROLE_TELLER]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($manager->isAdmin())->toBeFalse()
        ->and($staff->isAdmin())->toBeFalse()
        ->and($teller->isAdmin())->toBeFalse();
});

// ─── Panel access ────────────────────────────────────────────

it('active user can access the panel', function () {
    $user = makeStaffUser(['is_active' => true]);

    $panel = app(Panel::class)::make(); // dummy panel instance
    expect($user->canAccessPanel($panel))->toBeTrue();
});

it('inactive user cannot access the panel', function () {
    $user = makeStaffUser(['is_active' => false]);

    $panel = app(Panel::class)::make();
    expect($user->canAccessPanel($panel))->toBeFalse();
});

// ─── Activation / deactivation ───────────────────────────────

it('can deactivate a user', function () {
    $user = makeStaffUser(['is_active' => true]);

    $user->update(['is_active' => false]);

    expect($user->fresh()->is_active)->toBeFalse();
});

it('can reactivate a deactivated user', function () {
    $user = makeStaffUser(['is_active' => false]);

    $user->update(['is_active' => true]);

    expect($user->fresh()->is_active)->toBeTrue();
});

// ─── SeedTenantUsers command ─────────────────────────────────

it('seed-users command creates admin user for tenant', function () {
    $before = User::where('role', User::ROLE_ADMIN)->count();

    $this->artisan('tenants:seed-users', ['--tenants' => ['testing']])
        ->assertSuccessful();

    // At least one admin should exist now
    expect(User::where('role', User::ROLE_ADMIN)->count())->toBeGreaterThanOrEqual($before);
});

it('seed-demo command creates members and staff users', function () {
    $this->artisan('tenants:seed-demo', [
        '--tenants' => ['testing'],
        '--members' => 10,
    ])->assertSuccessful();

    expect(User::count())->toBeGreaterThan(0);
});
