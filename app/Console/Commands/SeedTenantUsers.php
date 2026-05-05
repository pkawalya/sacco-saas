<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Concerns\HasATenantsOption;

class SeedTenantUsers extends Command
{
    use HasATenantsOption;

    protected $signature = 'tenants:seed-users
                            {--tenants=* : Specific tenant IDs to seed (omit for all)}
                            {--password=password : Default password for created users}
                            {--force : Reset password even if user already exists}';

    protected $description = 'Seed an admin user into each tenant database for local development.';

    public function handle(): int
    {
        $password = $this->option('password');
        $force = $this->option('force');
        $tenants = Tenant::with('owner')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found in the central database.');

            return self::FAILURE;
        }

        $this->info("Seeding admin users into {$tenants->count()} tenant(s)...");
        $this->newLine();

        foreach ($tenants as $tenant) {
            $owner = $tenant->owner;
            $email = $owner?->email ?? ($tenant->id.'@sacco.local');
            $name = $owner?->name ?? $tenant->name.' Admin';

            $tenant->run(function () use ($email, $name, $password, $force, $tenant) {
                $existing = TenantUser::where('email', $email)->first();

                if ($existing && ! $force) {
                    $this->line("  <fg=yellow>~</> {$tenant->id} → {$email} (already exists, use --force to reset pw)");

                    return;
                }

                if ($existing && $force) {
                    $existing->update([
                        'password' => Hash::make($password),
                        'role' => TenantUser::ROLE_ADMIN,
                        'is_active' => true,
                    ]);
                    $this->line("  <fg=blue>↻</> {$tenant->id} → {$email} (password reset)");

                    return;
                }

                TenantUser::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => TenantUser::ROLE_ADMIN,
                    'is_active' => true,
                ]);
                $this->line("  <fg=green>✓</> {$tenant->id} → {$email} (created)");
            });
        }

        $this->newLine();
        $this->info("Done. Default password: <fg=cyan>{$password}</>");

        return self::SUCCESS;
    }
}
