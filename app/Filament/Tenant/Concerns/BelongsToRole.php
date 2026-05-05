<?php

namespace App\Filament\Tenant\Concerns;

use App\Services\ModuleService;

/**
 * Trait for Filament Resources that restrict access based on user roles and modules.
 *
 * Usage in a Resource class:
 *   use BelongsToRole;
 *   protected static string $moduleKey = 'module_name';
 *   protected static array $allowedRoles = ['admin', 'manager'];
 */
trait BelongsToRole
{
    /**
     * Only allow access if the module is active AND the current user's role is in the allowed roles.
     */
    public static function canAccess(): bool
    {
        // Check module access first
        if (isset(static::$moduleKey) && static::$moduleKey !== '') {
            if (! ModuleService::isActive(static::$moduleKey)) {
                return false;
            }
        }

        // Check role access
        if (! empty(static::$allowedRoles)) {
            if (! in_array(auth()->user()?->role, static::$allowedRoles, true)) {
                return false;
            }
        }

        return true;
    }
}
