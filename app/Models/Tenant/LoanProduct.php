<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $product_code
 * @property string $product_name
 * @property float $interest_rate
 * @property string $interest_method
 * @property bool $four_eyes_disbursement
 */
class LoanProduct extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_TERM = 'term';

    public const TYPE_REVOLVING = 'revolving';

    public const TYPE_MORTGAGE = 'mortgage';

    public const TYPE_GROUP = 'group';

    public const TYPE_EMERGENCY = 'emergency';

    public const TYPE_SCHOOL_FEES = 'school_fees';

    public const TYPES = [
        self::TYPE_TERM => 'Term Loan',
        self::TYPE_REVOLVING => 'Revolving Credit',
        self::TYPE_MORTGAGE => 'Mortgage',
        self::TYPE_GROUP => 'Group Loan',
        self::TYPE_EMERGENCY => 'Emergency Loan',
        self::TYPE_SCHOOL_FEES => 'School Fees Loan',
    ];

    public const METHOD_REDUCING = 'reducing';

    public const METHOD_FLAT = 'flat';

    public const METHOD_COMPOUND = 'compound';

    public const METHODS = [
        self::METHOD_REDUCING => 'Reducing Balance',
        self::METHOD_FLAT => 'Flat Rate',
        self::METHOD_COMPOUND => 'Compound Interest',
    ];

    protected $fillable = [
        'product_code',
        'product_name',
        'product_type',
        'description',
        'interest_rate',
        'interest_method',
        'interest_period',
        'processing_fee_rate',
        'processing_fee_fixed',
        'processing_fee_upfront',
        'maintenance_fee_monthly',
        'insurance_rate',
        'penalty_rate_daily',
        'penalty_rate_monthly',
        'grace_period_days',
        'minimum_tenure_months',
        'maximum_tenure_months',
        'minimum_amount',
        'maximum_amount',
        'maximum_multiplier',
        'minimum_guarantors',
        'maximum_guarantors',
        'collateral_required',
        'minimum_coverage_ratio',
        'approval_levels',
        'four_eyes_disbursement',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interest_rate' => 'decimal:4',
            'processing_fee_rate' => 'decimal:4',
            'processing_fee_fixed' => 'decimal:2',
            'maintenance_fee_monthly' => 'decimal:2',
            'insurance_rate' => 'decimal:4',
            'penalty_rate_daily' => 'decimal:4',
            'penalty_rate_monthly' => 'decimal:4',
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'maximum_multiplier' => 'decimal:4',
            'minimum_coverage_ratio' => 'decimal:4',
            'approval_levels' => 'array',
            'processing_fee_upfront' => 'boolean',
            'collateral_required' => 'boolean',
            'four_eyes_disbursement' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'product_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class, 'product_id');
    }

    // ─── Business Logic ─────────────────────────────────

    /**
     * Compute the processing fee for a given principal amount.
     */
    public function computeProcessingFee(float $principal): float
    {
        $percentageFee = $principal * ((float) $this->processing_fee_rate / 100);
        $fixedFee = (float) $this->processing_fee_fixed;

        return round(max($percentageFee, $fixedFee), 2);
    }

    /**
     * Compute the insurance amount for a given principal amount.
     */
    public function computeInsurance(float $principal): float
    {
        return round($principal * ((float) $this->insurance_rate / 100), 2);
    }

    /**
     * Get the approval level configuration for a given loan amount.
     *
     * @return array<string, mixed>|null
     */
    public function getApprovalLevelFor(float $amount): ?array
    {
        if (empty($this->approval_levels)) {
            return null;
        }

        foreach ($this->approval_levels as $level) {
            $min = (float) ($level['min'] ?? 0);
            $max = isset($level['max']) ? (float) $level['max'] : PHP_FLOAT_MAX;

            if ($amount >= $min && $amount <= $max) {
                return $level;
            }
        }

        return null;
    }
}
