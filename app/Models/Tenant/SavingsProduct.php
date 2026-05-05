<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $product_code
 * @property string $product_name
 * @property string $product_type
 * @property float $interest_rate
 * @property float $minimum_balance
 * @property bool $has_tiered_rates
 */
class SavingsProduct extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_REGULAR = 'regular';

    public const TYPE_FIXED_DEPOSIT = 'fixed_deposit';

    public const TYPE_CURRENT = 'current';

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_CHILDREN = 'children';

    public const TYPES = [
        self::TYPE_REGULAR => 'Regular Savings',
        self::TYPE_FIXED_DEPOSIT => 'Fixed Deposit',
        self::TYPE_CURRENT => 'Current Account',
        self::TYPE_HOLIDAY => 'Holiday Savings',
        self::TYPE_CHILDREN => "Children's Savings",
    ];

    public const COMPUTATION_DAILY_AVERAGE = 'daily_average';

    public const COMPUTATION_MIN_MONTHLY = 'min_monthly';

    public const COMPUTATION_EOM_BALANCE = 'eom_balance';

    public const COMPUTATIONS = [
        self::COMPUTATION_DAILY_AVERAGE => 'Daily Average Balance',
        self::COMPUTATION_MIN_MONTHLY => 'Minimum Monthly Balance',
        self::COMPUTATION_EOM_BALANCE => 'End-of-Month Balance',
    ];

    protected $fillable = [
        'product_code',
        'product_name',
        'product_type',
        'description',
        'interest_rate',
        'interest_computation',
        'interest_posting_cycle',
        'has_tiered_rates',
        'tier_rates',
        'minimum_balance',
        'maximum_balance',
        'minimum_opening_deposit',
        'maximum_single_deposit',
        'maximum_single_withdrawal',
        'free_withdrawals_per_month',
        'below_minimum_penalty',
        'early_withdrawal_penalty_rate',
        'minimum_tenure_months',
        'maximum_tenure_months',
        'auto_rollover',
        'is_active',
        'is_joint_allowed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interest_rate' => 'decimal:4',
            'minimum_balance' => 'decimal:2',
            'maximum_balance' => 'decimal:2',
            'minimum_opening_deposit' => 'decimal:2',
            'maximum_single_deposit' => 'decimal:2',
            'maximum_single_withdrawal' => 'decimal:2',
            'below_minimum_penalty' => 'decimal:2',
            'early_withdrawal_penalty_rate' => 'decimal:4',
            'has_tiered_rates' => 'boolean',
            'tier_rates' => 'array',
            'auto_rollover' => 'boolean',
            'is_active' => 'boolean',
            'is_joint_allowed' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function accounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class, 'product_id');
    }

    public function fixedDeposits(): HasMany
    {
        return $this->hasMany(FixedDeposit::class, 'product_id');
    }

    public function interestAccruals(): HasMany
    {
        return $this->hasMany(InterestAccrual::class, 'product_id');
    }

    // ─── Business Logic ─────────────────────────────────

    /**
     * Get the applicable interest rate for a given balance (FR-SD-003).
     * If tiered rates are configured, pick the matching tier; otherwise return the base rate.
     */
    public function getApplicableRate(float $balance): float
    {
        if (! $this->has_tiered_rates || empty($this->tier_rates)) {
            return (float) $this->interest_rate;
        }

        foreach ($this->tier_rates as $tier) {
            $min = (float) ($tier['min_balance'] ?? 0);
            $max = isset($tier['max_balance']) ? (float) $tier['max_balance'] : PHP_FLOAT_MAX;

            if ($balance >= $min && $balance <= $max) {
                return (float) $tier['rate'];
            }
        }

        return (float) $this->interest_rate;
    }
}
