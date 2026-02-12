<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Subscription');
    }

    public function view(AuthUser $authUser, Subscription $subscription): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('View:Subscription') && $subscription->tenant->central_user_id === $authUser->id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Create:Subscription');
    }

    public function update(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Update:Subscription');
    }

    public function delete(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Delete:Subscription');
    }

    public function restore(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Restore:Subscription');
    }

    public function forceDelete(AuthUser $authUser, Subscription $subscription): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('ForceDelete:Subscription');
    }
}
