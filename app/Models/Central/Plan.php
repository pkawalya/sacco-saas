<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Plan model defines the available subscription packages.
 *
 * @property string $name
 * @property string $slug
 * @property float $price
 * @property string $currency
 * @property string $billing_cycle
 * @property int $duration_months
 * @property string|null $description
 * @property array|null $data
 * @property array|null $modules
 * @property int $stage
 * @property bool $is_active
 * @property bool $is_custom
 * @property bool $support_custom_domain
 */
class Plan extends Model
{
    use CentralConnection, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'currency',
        'billing_cycle',
        'duration_months',
        'description',
        'data',
        'modules',
        'stage',
        'is_active',
        'is_custom',
        'support_custom_domain',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'data' => 'array',
            'modules' => 'array',
            'stage' => 'integer',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
            'support_custom_domain' => 'boolean',
        ];
    }

    /**
     * Tenants using this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Check if this plan includes a specific module.
     */
    public function hasModule(string $moduleKey): bool
    {
        return in_array($moduleKey, $this->modules ?? [], true);
    }

    /**
     * Get the list of active module definitions for this plan.
     *
     * @return array<string, array{label: string, description: string, stage: int, icon: string}>
     */
    public function getActiveModuleDefinitions(): array
    {
        $registry = config('modules', []);
        $activeKeys = $this->modules ?? [];

        return array_filter(
            $registry,
            fn (string $key): bool => in_array($key, $activeKeys, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
