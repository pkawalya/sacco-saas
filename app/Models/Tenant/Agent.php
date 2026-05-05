<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agent banking entity (FR-CH-030–031).
 *
 * @property int $id
 * @property string $agent_code
 * @property string $agent_name
 * @property float $float_balance
 * @property float $float_limit
 * @property float $commission_rate
 * @property string $status
 */
class Agent extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DEACTIVATED = 'deactivated';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
        self::STATUS_DEACTIVATED => 'Deactivated',
    ];

    protected $fillable = [
        'agent_code',
        'agent_name',
        'business_name',
        'phone',
        'branch_code',
        'float_balance',
        'float_limit',
        'daily_transaction_limit',
        'commission_rate',
        'total_commission_earned',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'float_balance' => 'decimal:2',
            'float_limit' => 'decimal:2',
            'daily_transaction_limit' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'total_commission_earned' => 'decimal:2',
        ];
    }

    public function agentTransactions(): HasMany
    {
        return $this->hasMany(AgentTransaction::class, 'agent_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function hasFloatCapacity(float $amount): bool
    {
        return (float) $this->float_balance >= $amount;
    }

    public function computeCommission(float $amount): float
    {
        return round($amount * ((float) $this->commission_rate / 100), 2);
    }
}
