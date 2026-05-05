<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configurable revenue source linked to GL accounts.
 *
 * FR-RE-010–014: Revenue sources, WHT automation, recognition basis.
 *
 * @property int $id
 * @property string $source_code
 * @property string $source_name
 * @property string|null $description
 * @property string $revenue_type
 * @property string $recognition_basis
 * @property int $gl_account_id
 * @property int|null $wht_account_id
 * @property float $wht_rate
 * @property bool $wht_applicable
 * @property bool $is_active
 * @property string $frequency
 */
class RevenueSource extends Model
{
    // ─── Type Constants ─────────────────────────────────────────
    public const TYPE_INTEREST = 'interest';

    public const TYPE_FEE = 'fee';

    public const TYPE_COMMISSION = 'commission';

    public const TYPE_PENALTY = 'penalty';

    public const TYPE_INVESTMENT = 'investment';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_INTEREST => 'Interest Income',
        self::TYPE_FEE => 'Fee Income',
        self::TYPE_COMMISSION => 'Commission',
        self::TYPE_PENALTY => 'Penalty Income',
        self::TYPE_INVESTMENT => 'Investment Income',
        self::TYPE_OTHER => 'Other Income',
    ];

    // ─── Recognition Basis Constants ────────────────────────────
    public const RECOGNITION_ACCRUAL = 'accrual';

    public const RECOGNITION_CASH = 'cash';

    public const RECOGNITION_HYBRID = 'hybrid';

    public const RECOGNITION_BASES = [
        self::RECOGNITION_ACCRUAL => 'Accrual',
        self::RECOGNITION_CASH => 'Cash',
        self::RECOGNITION_HYBRID => 'Hybrid',
    ];

    // ─── Frequency Constants ────────────────────────────────────
    public const FREQUENCY_ONE_TIME = 'one_time';

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_ANNUALLY = 'annually';

    public const FREQUENCIES = [
        self::FREQUENCY_ONE_TIME => 'One-Time',
        self::FREQUENCY_DAILY => 'Daily',
        self::FREQUENCY_MONTHLY => 'Monthly',
        self::FREQUENCY_QUARTERLY => 'Quarterly',
        self::FREQUENCY_ANNUALLY => 'Annually',
    ];

    protected $fillable = [
        'source_code',
        'source_name',
        'description',
        'revenue_type',
        'recognition_basis',
        'gl_account_id',
        'wht_account_id',
        'wht_rate',
        'wht_applicable',
        'is_active',
        'frequency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wht_rate' => 'decimal:2',
            'wht_applicable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function whtAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'wht_account_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('revenue_type', $type);
    }

    // ─── WHT Helpers (FR-RE-010–014) ────────────────────────────

    /**
     * Compute the withholding tax on a gross amount.
     */
    public function computeWht(float $grossAmount): float
    {
        if (! $this->wht_applicable || $this->wht_rate <= 0) {
            return 0.0;
        }

        return round($grossAmount * ($this->wht_rate / 100), 2);
    }

    /**
     * Compute the net amount after WHT deduction.
     */
    public function computeNetAmount(float $grossAmount): float
    {
        return round($grossAmount - $this->computeWht($grossAmount), 2);
    }
}
