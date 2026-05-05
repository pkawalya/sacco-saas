<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cost allocation linking cost centres to GL accounts with flexible methods.
 *
 * FR-CC-003: Internal charge-backs with transfer pricing.
 * FR-CC-004: Cost Centre P&L reporting data.
 *
 * @property int $id
 * @property int $cost_centre_id
 * @property int $gl_account_id
 * @property int $fiscal_year
 * @property int|null $period_month
 * @property float $allocated_amount
 * @property float $actual_amount
 * @property string $allocation_method
 * @property float $allocation_percentage
 * @property int|null $chargeback_from_id
 * @property float $transfer_price
 * @property string|null $chargeback_description
 * @property string $status
 */
class CostAllocation extends Model
{
    // ─── Method Constants ───────────────────────────────────────
    public const METHOD_DIRECT = 'direct';

    public const METHOD_PROPORTIONAL = 'proportional';

    public const METHOD_HEADCOUNT = 'headcount';

    public const METHOD_REVENUE_BASED = 'revenue_based';

    public const METHOD_ACTIVITY_BASED = 'activity_based';

    public const METHODS = [
        self::METHOD_DIRECT => 'Direct',
        self::METHOD_PROPORTIONAL => 'Proportional',
        self::METHOD_HEADCOUNT => 'Headcount-Based',
        self::METHOD_REVENUE_BASED => 'Revenue-Based',
        self::METHOD_ACTIVITY_BASED => 'Activity-Based',
    ];

    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_ACTIVE = 'active';

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_FROZEN => 'Frozen',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'cost_centre_id',
        'gl_account_id',
        'fiscal_year',
        'period_month',
        'allocated_amount',
        'actual_amount',
        'allocation_method',
        'allocation_percentage',
        'chargeback_from_id',
        'transfer_price',
        'chargeback_description',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'allocation_percentage' => 'decimal:4',
            'transfer_price' => 'decimal:2',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function chargebackFrom(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class, 'chargeback_from_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForYear(Builder $query, int $year): void
    {
        $query->where('fiscal_year', $year);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeChargebacks(Builder $query): void
    {
        $query->whereNotNull('chargeback_from_id');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Compute the allocated amount based on method and percentage.
     */
    public function computeAllocation(float $totalAmount): float
    {
        return round($totalAmount * ((float) $this->allocation_percentage / 100), 2);
    }

    /**
     * Variance between allocated and actual.
     */
    public function getVarianceAttribute(): float
    {
        return round((float) $this->allocated_amount - (float) $this->actual_amount, 2);
    }

    /**
     * Whether this is an internal charge-back.
     */
    public function isChargeback(): bool
    {
        return $this->chargeback_from_id !== null;
    }

    /**
     * Record actual spend.
     */
    public function recordActual(float $amount): void
    {
        $this->increment('actual_amount', $amount);
    }
}
