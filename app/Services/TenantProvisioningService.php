<?php

namespace App\Services;

use App\Jobs\Central\CreateFrameworkCacheDirForTenant;
use App\Jobs\Central\MarkTenantAsProvisioned;
use App\Jobs\Central\SeedTenantAdminUser;
use App\Jobs\Central\SeedTenantDemoData;
use App\Models\Central\Invoice;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Bus;
use Stancl\Tenancy\Jobs;

/**
 * This service handles the 'Provisioning' process or setting up the tenant infrastructure.
 * Usually called after the payment invoice is confirmed as paid.
 */
class TenantProvisioningService
{
    /**
     * Provision a tenant after invoice payment.
     */
    public function provisionTenant(Invoice $invoice): void
    {
        $tenant = Tenant::find($invoice->tenant_id);

        if (! $tenant) {
            \Log::error('Tenant not found for invoice: '.$invoice->id);
            throw new \Exception('Tenant not found');
        }

        $this->provision($tenant);
    }

    /**
     * Provision a tenant manually (e.g. from Admin Panel).
     */
    public function provisionManual(Tenant $tenant): void
    {
        $this->provision($tenant);
    }

    /**
     * Internal logic for provisioning.
     */
    protected function provision(Tenant $tenant): void
    {
        try {
            // 1. Create domain for the tenant if it doesn't exist
            // Tenant subdomain: {id}.wakacosacco.com (base domain extracted from central_domain)
            $centralDomain = config('tenancy.central_domain');
            $baseDomain = preg_replace('/^[^.]+\./', '', $centralDomain);
            $domain = $tenant->domain_name ?? $tenant->id.'.'.$baseDomain;

            if (! $tenant->domains()->where('domain', $domain)->exists()) {
                $tenant->domains()->create([
                    'domain' => $domain,
                ]);
            }

            // 2. Run the database creation and migration job chain sequentially
            Bus::chain([
                new Jobs\CreateDatabase($tenant),      // Create new DB
                new Jobs\MigrateDatabase($tenant),     // Run migrations in database/migrations/tenant folder
                new CreateFrameworkCacheDirForTenant($tenant), // Setup isolated cache folder
                new SeedTenantAdminUser($tenant),              // Seed admin user for tenant panel
                new SeedTenantDemoData($tenant),               // Seed demo data for tenant panel

                new MarkTenantAsProvisioned($tenant->id), // Mark as finished
            ])->dispatch();

            // 3. Activate the subscription
            $subscription = Subscription::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->first();

            if ($subscription) {
                $subscription->update(['status' => 'active']);
            }

        } catch (\Exception $e) {
            \Log::error('Provisioning failed for tenant '.$tenant->id.': '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate if the tenant is ready to be provisioned via Invoice.
     */
    public function canProvisionTenant(Invoice $invoice): bool
    {
        if ($invoice->status !== 'paid') {
            return false;
        }

        $tenant = Tenant::find($invoice->tenant_id);

        if (! $tenant || $tenant->is_provisioned) {
            return false;
        }

        return true;
    }
}
