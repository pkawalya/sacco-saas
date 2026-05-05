<?php

namespace App\Jobs\Central;

use App\Mail\StaffWelcomeMail;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Creates or updates an admin user in a tenant database from the central super admin panel.
 * Sends welcome email with login credentials when a new user is created.
 */
class CreateTenantAdminFromCentral implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, password: string, role: string, send_email: bool}  $userData
     */
    public function __construct(
        protected Tenant $tenant,
        protected array $userData
    ) {}

    public function handle(): void
    {
        $this->tenant->run(function (): void {
            $existingUser = TenantUser::where('email', $this->userData['email'])->first();

            if ($existingUser) {
                $existingUser->update([
                    'name' => $this->userData['name'],
                    'password' => Hash::make($this->userData['password']),
                    'role' => $this->userData['role'],
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
                $tenantUser = $existingUser;
            } else {
                $tenantUser = TenantUser::create([
                    'name' => $this->userData['name'],
                    'email' => $this->userData['email'],
                    'password' => Hash::make($this->userData['password']),
                    'role' => $this->userData['role'],
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
            }

            if ($this->userData['send_email'] ?? false) {
                $domain = $this->tenant->domains()->first()?->domain;
                $panelUrl = $domain ? "https://{$domain}" : '#';

                Mail::to($tenantUser->email)->send(new StaffWelcomeMail(
                    staffUser: $tenantUser,
                    plainPassword: $this->userData['password'],
                    saccoName: $this->tenant->name ?? $this->tenant->id,
                    panelUrl: $panelUrl,
                ));
            }
        });
    }
}
