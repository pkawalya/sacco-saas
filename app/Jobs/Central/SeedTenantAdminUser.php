<?php

namespace App\Jobs\Central;

use App\Models\Central\Tenant;
use App\Models\Central\User as CentralUser;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds an admin user into the tenant database during provisioning.
 *
 * The admin user's email mirrors the central owner's email so SACCO
 * owners use the same credentials across both panels.
 */
class SeedTenantAdminUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Tenant $tenant) {}

    public function handle(): void
    {
        $centralUser = CentralUser::find($this->tenant->central_user_id);

        $email = $centralUser?->email ?? ($this->tenant->id.'@sacco.local');
        $name = $centralUser?->name ?? $this->tenant->name.' Admin';

        $this->tenant->run(function () use ($email, $name) {
            TenantUser::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => TenantUser::ROLE_ADMIN,
                    'is_active' => true,
                ]
            );
        });
    }
}
