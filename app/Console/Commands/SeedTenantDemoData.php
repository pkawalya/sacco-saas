<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\Member;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Concerns\HasATenantsOption;

class SeedTenantDemoData extends Command
{
    use HasATenantsOption;

    protected $signature = 'tenants:seed-demo
                            {--tenants=* : Specific tenant IDs to seed (omit for all)}
                            {--members=30 : Number of demo members to create per tenant}
                            {--fresh : Truncate existing data before seeding}';

    protected $description = 'Seed realistic demo data (members, staff) into tenant databases for development.';

    public function handle(): int
    {
        $memberCount = (int) $this->option('members');
        $fresh = $this->option('fresh');

        $tenants = Tenant::with('owner')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found. Run php artisan db:seed first.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Seeding <fg=cyan>{$tenant->id}</> ({$tenant->name})...");

            $tenant->run(function () use ($memberCount, $fresh, $tenant) {
                if ($fresh) {
                    Member::query()->forceDelete();
                    $this->line('  <fg=yellow>!</> Existing members cleared.');
                }

                // ─── Staff Users ────────────────────────────────
                $this->seedStaffUsers($tenant);

                // ─── Members ────────────────────────────────────
                $existing = Member::count();
                if ($existing >= $memberCount && ! $fresh) {
                    $this->line("  <fg=yellow>~</> Members: {$existing} already exist (skipping). Use --fresh to reseed.");

                    return;
                }

                $toCreate = $memberCount - $existing;

                // Mix of statuses for realistic demo data
                Member::factory()->count((int) ($toCreate * 0.65))->active()->create();
                Member::factory()->count((int) ($toCreate * 0.20))->applicant()->create();
                Member::factory()->count((int) ($toCreate * 0.08))->dormant()->create();
                Member::factory()->count((int) ($toCreate * 0.05))->suspended()->create();
                Member::factory()->count(max(1, (int) ($toCreate * 0.02)))->exited()->create();

                $total = Member::count();
                $this->line("  <fg=green>✓</> Members: {$total} total");
            });
        }

        $this->newLine();
        $this->info('Demo data seeding complete.');
        $this->line('  Run <fg=cyan>php artisan tenants:seed-users</> to ensure all tenant admins have login credentials.');

        return self::SUCCESS;
    }

    private function seedStaffUsers(Tenant $tenant): void
    {
        $owner = $tenant->owner;
        $domain = $tenant->id;

        $staff = [
            [
                'name' => $owner?->name ?? $tenant->name.' Admin',
                'email' => $owner?->email ?? "admin@{$domain}.local",
                'role' => TenantUser::ROLE_ADMIN,
            ],
            [
                'name' => 'Branch Manager',
                'email' => "manager@{$domain}.sacco",
                'role' => TenantUser::ROLE_MANAGER,
            ],
            [
                'name' => 'Loans Officer',
                'email' => "loans@{$domain}.sacco",
                'role' => TenantUser::ROLE_STAFF,
            ],
            [
                'name' => 'Front Desk Teller',
                'email' => "teller@{$domain}.sacco",
                'role' => TenantUser::ROLE_TELLER,
            ],
        ];

        $created = 0;
        foreach ($staff as $s) {
            TenantUser::firstOrCreate(
                ['email' => $s['email']],
                array_merge($s, ['password' => Hash::make('password'), 'is_active' => true])
            ) && $created++;
        }

        if ($created > 0) {
            $this->line("  <fg=green>✓</> Staff users: {$created} created (password: <fg=cyan>password</>)");
        } else {
            $this->line('  <fg=yellow>~</> Staff users: all already exist');
        }
    }
}
