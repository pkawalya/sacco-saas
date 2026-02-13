<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Central\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Central\User as AuthUser;

class PlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Plan');
    }

    public function view(AuthUser $authUser, Plan $plan): bool
    {
        return $authUser->can('View:Plan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Create:Plan');
    }

    public function update(AuthUser $authUser, Plan $plan): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Update:Plan');
    }

    public function delete(AuthUser $authUser, Plan $plan): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Delete:Plan');
    }

    public function restore(AuthUser $authUser, Plan $plan): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Restore:Plan');
    }

    public function forceDelete(AuthUser $authUser, Plan $plan): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('ForceDelete:Plan');
    }
}
