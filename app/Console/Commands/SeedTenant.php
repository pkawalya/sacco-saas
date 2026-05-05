<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Database\Seeders\Tenant\FullTenantSeeder;
use Illuminate\Console\Command;

class SeedTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:seed {tenant_id : The tenant ID to seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo data for a specific tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID '{$tenantId}' not found.");

            return 1;
        }

        $this->info("Seeding tenant: {$tenant->name} ({$tenant->id})");

        $tenant->run(function () {
            $seeder = new FullTenantSeeder;
            $seeder->setCommand($this);
            $seeder->run();
        });

        $this->info('Tenant seeding completed successfully!');
    }
}
