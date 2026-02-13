<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Central\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Central\User as AuthUser;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tenant');
    }

    public function view(AuthUser $authUser, Tenant $tenant): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('View:Tenant') && $tenant->central_user_id === $authUser->id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tenant');
    }

    public function update(AuthUser $authUser, Tenant $tenant): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Update:Tenant') && $tenant->central_user_id === $authUser->id;
    }

    public function delete(AuthUser $authUser, Tenant $tenant): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return false;
    }

    public function restore(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('Restore:Tenant') && $authUser->hasRole('super_admin');
    }

    public function forceDelete(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('ForceDelete:Tenant') && $authUser->hasRole('super_admin');
    }
}
