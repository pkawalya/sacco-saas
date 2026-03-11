<?php

namespace App\Filament\Tenant\Concerns;

use App\Services\ModuleService;

/**
 * Trait for Filament Resources that belong to a gated module.
 *
 * Usage in a Resource class:
 *   use BelongsToModule;
 *   protected static string $moduleKey = 'member_management';
 */
trait BelongsToModule
{
    /**
     * Only register this resource if the module is active for the current tenant.
     */
    public static function canAccess(): bool
    {
        if (! isset(static::$moduleKey) || static::$moduleKey === '') {
            return true;
        }

        return ModuleService::isActive(static::$moduleKey);
    }
}
