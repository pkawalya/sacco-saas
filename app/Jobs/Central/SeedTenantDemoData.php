<?php

namespace App\Jobs\Central;

use App\Models\Central\Tenant;
use Database\Seeders\Tenant\FullTenantSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Seeds demo data into the tenant database during provisioning.
 */
class SeedTenantDemoData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Tenant $tenant) {}

    public function handle(): void
    {
        $this->tenant->run(function () {
            $seeder = new FullTenantSeeder;
            $seeder->run();
        });
    }
}
