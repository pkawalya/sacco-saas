<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Central\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Central\User as AuthUser;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Invoice');
    }

    public function view(AuthUser $authUser, Invoice $invoice): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('View:Invoice') && $invoice->tenant->central_user_id === $authUser->id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Create:Invoice');
    }

    public function update(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Update:Invoice');
    }

    public function delete(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Delete:Invoice');
    }

    public function restore(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('Restore:Invoice');
    }

    public function forceDelete(AuthUser $authUser, Invoice $invoice): bool
    {
        return $authUser->hasRole('super_admin') && $authUser->can('ForceDelete:Invoice');
    }
}
