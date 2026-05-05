<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Investment register with type-specific fields and performance tracking.
 *
 * FR-RE-030–034: Investment portfolio management.
 *
 * @property int $id
 * @property string $investment_code
 * @property string $name
 * @property string|null $description
 * @property string $investment_type
 * @property string|null $counterparty
 * @property int $gl_account_id
 * @property int|null $income_account_id
 * @property float $face_value
 * @property float $purchase_price
 * @property float $current_value
 * @property float $accrued_income
 * @property string $currency
 * @property float $interest_rate
 * @property float $expected_return
 * @property string $purchase_date
 * @property string|null $maturity_date
 * @property string|null $last_valuation_date
 * @property string $status
 * @property string|null $reference_number
 * @property array|null $metadata
 */
class Investment extends Model
{
    // ─── Type Constants ─────────────────────────────────────────
    public const TYPE_TREASURY_BILL = 'treasury_bill';

    public const TYPE_TREASURY_BOND = 'treasury_bond';

    public const TYPE_FIXED_DEPOSIT = 'fixed_deposit';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_PROPERTY = 'property';

    public const TYPE_MONEY_MARKET = 'money_market';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_TREASURY_BILL => 'Treasury Bill',
        self::TYPE_TREASURY_BOND => 'Treasury Bond',
        self::TYPE_FIXED_DEPOSIT => 'Fixed Deposit',
        self::TYPE_EQUITY => 'Equity/Shares',
        self::TYPE_PROPERTY => 'Property',
        self::TYPE_MONEY_MARKET => 'Money Market Fund',
        self::TYPE_OTHER => 'Other',
    ];

    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_ACTIVE = 'active';

    public const STATUS_MATURED = 'matured';

    public const STATUS_SOLD = 'sold';

    public const STATUS_WRITTEN_OFF = 'written_off';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_MATURED => 'Matured',
        self::STATUS_SOLD => 'Sold',
        self::STATUS_WRITTEN_OFF => 'Written Off',
    ];

    protected $fillable = [
        'investment_code',
        'name',
        'description',
        'investment_type',
        'counterparty',
        'gl_account_id',
        'income_account_id',
        'face_value',
        'purchase_price',
        'current_value',
        'accrued_income',
        'currency',
        'interest_rate',
        'expected_return',
        'purchase_date',
        'maturity_date',
        'last_valuation_date',
        'status',
        'reference_number',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'face_value' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'current_value' => 'decimal:2',
            'accrued_income' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'expected_return' => 'decimal:2',
            'purchase_date' => 'date',
            'maturity_date' => 'date',
            'last_valuation_date' => 'date',
            'metadata' => 'array',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'income_account_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

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
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('investment_type', $type);
    }

    // ─── Performance Helpers (FR-RE-030–034) ────────────────────

    /**
     * Unrealised gain/loss = current value - purchase price.
     */
    public function getUnrealisedGainLossAttribute(): float
    {
        return round((float) $this->current_value - (float) $this->purchase_price, 2);
    }

    /**
     * Return on investment as percentage.
     */
    public function getRoiAttribute(): float
    {
        if ((float) $this->purchase_price === 0.0) {
            return 0.0;
        }

        return round((((float) $this->current_value - (float) $this->purchase_price) / (float) $this->purchase_price) * 100, 2);
    }

    /**
     * Check if the investment has matured.
     */
    public function isMatured(): bool
    {
        return $this->maturity_date && $this->maturity_date->isPast();
    }

    /**
     * Days until maturity, or 0 if already matured.
     */
    public function getDaysToMaturityAttribute(): int
    {
        if (! $this->maturity_date || $this->maturity_date->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->maturity_date);
    }

    /**
     * Mark the investment as matured.
     */
    public function markMatured(): void
    {
        $this->update(['status' => self::STATUS_MATURED]);
    }

    /**
     * Update the current value (mark-to-market).
     */
    public function revalue(float $newValue): void
    {
        $this->update([
            'current_value' => $newValue,
            'last_valuation_date' => now()->toDateString(),
        ]);
    }
}
