<?php

namespace App\Services;

use App\Models\Central\Plan;
use App\Models\Central\Tenant;

/**
 * Service to check module access based on tenant subscription.
 *
 * Usage:
 *   ModuleService::isActive('member_management')  — returns bool
 *   ModuleService::gate('member_management')       — aborts 403 if inactive
 *   ModuleService::getActiveModules()              — returns active module keys
 */
class ModuleService
{
    /**
     * Check if a module is active for the current tenant.
     */
    public static function isActive(string $moduleKey): bool
    {
        $plan = static::getCurrentPlan();

        if (! $plan) {
            return false;
        }

        return $plan->hasModule($moduleKey);
    }

    /**
     * Abort with 403 if the module is not active.
     */
    public static function gate(string $moduleKey): void
    {
        if (! static::isActive($moduleKey)) {
            $label = config("modules.{$moduleKey}.label", $moduleKey);
            abort(403, "The \"{$label}\" module is not included in your subscription plan.");
        }
    }

    /**
     * Get all active module keys for the current tenant.
     *
     * @return array<int, string>
     */
    public static function getActiveModules(): array
    {
        $plan = static::getCurrentPlan();

        return $plan?->modules ?? [];
    }

    /**
     * Get the full module registry with an `active` flag per module.
     *
     * @return array<string, array{label: string, description: string, stage: int, icon: string, active: bool}>
     */
    public static function getModuleRegistry(): array
    {
        $activeModules = static::getActiveModules();
        $registry = config('modules', []);

        return array_map(
            fn (array $module, string $key): array => array_merge($module, [
                'active' => in_array($key, $activeModules, true),
            ]),
            $registry,
            array_keys($registry),
        );
    }

    /**
     * Get the plan for the current tenant.
     */
    protected static function getCurrentPlan(): ?Plan
    {
        /** @var Tenant|null $tenant */
        $tenant = tenant();

        if (! $tenant) {
            return null;
        }

        return $tenant->plan;
    }
}
