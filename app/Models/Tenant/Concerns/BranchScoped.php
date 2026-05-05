<?php

namespace App\Models\Tenant\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Scope queries to the user's branch for role-based data access.
 *
 * Usage: `use BranchScoped;` in your model. Models must have a `branch_code` column.
 * Admin and Manager roles bypass the scope.
 */
trait BranchScoped
{
    public static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            // Admin and managers see all data
            $role = $user->role ?? $user->getRoleNames()->first() ?? 'staff';
            if (in_array($role, ['admin', 'manager', 'super_admin'])) {
                return;
            }

            // Tellers and staff see only their branch
            $branchCode = $user->branch_code ?? null;
            if ($branchCode) {
                $query->where('branch_code', $branchCode);
            }
        });
    }
}
