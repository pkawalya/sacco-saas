<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $loan_number
 * @property string $status
 * @property float $outstanding_principal
 * @property float $outstanding_interest
 * @property float $outstanding_penalty
 * @property int $days_past_due
 * @property string $par_bucket
 */
class Loan extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_WRITTEN_OFF = 'written_off';

    public const STATUS_RESTRUCTURED = 'restructured';

    public const STATUSES = [
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_WRITTEN_OFF => 'Written Off',
        self::STATUS_RESTRUCTURED => 'Restructured',
    ];

    /**
     * PAR buckets with upper-bound days.
     * FR-LM-032: nightly recomputed.
     *
     * @var array<string, int>
     */
    public const PAR_BUCKETS = [
        'current' => 0,
        '1-30' => 30,
        '31-60' => 60,
        '61-90' => 90,
        '91-180' => 180,
        '180+' => PHP_INT_MAX,
    ];

    /**
     * Allocation order constants (configurable per product in future).
     */
    public const ALLOCATION_ORDER = ['penalty', 'interest', 'principal'];

    protected $fillable = [
        'loan_number',
        'member_id',
        'product_id',
        'application_id',
        'principal_amount',
        'approved_amount',
        'disbursed_amount',
        'tenure_months',
        'interest_rate',
        'interest_method',
        'processing_fee',
        'insurance_amount',
        'total_fees',
        'outstanding_principal',
        'outstanding_interest',
        'outstanding_penalty',
        'total_outstanding',
        'monthly_instalment',
        'first_repayment_date',
        'expected_maturity_date',
        'actual_maturity_date',
        'last_repayment_date',
        'days_past_due',
        'par_bucket',
        'amount_in_arrears',
        'disbursement_account',
        'disbursement_channel',
        'disbursed_by',
        'authorised_by',
        'disbursed_at',
        'status',
        'branch_code',
        'loan_officer_id',
        'parent_loan_id',
        'loan_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'disbursed_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'processing_fee' => 'decimal:2',
            'insurance_amount' => 'decimal:2',
            'total_fees' => 'decimal:2',
            'outstanding_principal' => 'decimal:2',
            'outstanding_interest' => 'decimal:2',
            'outstanding_penalty' => 'decimal:2',
            'total_outstanding' => 'decimal:2',
            'monthly_instalment' => 'decimal:2',
            'amount_in_arrears' => 'decimal:2',
            'first_repayment_date' => 'date',
            'expected_maturity_date' => 'date',
            'actual_maturity_date' => 'date',
            'last_repayment_date' => 'date',
            'disbursed_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'product_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'application_id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(AmortisationSchedule::class)->orderBy('instalment_number');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class)->orderByDesc('posted_at');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    public function collateral(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LoanApproval::class)->orderBy('approval_level');
    }

    public function parentLoan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_loan_id');
    }

    // ─── Scopes ─────────────────────────────────────────

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
    public function scopeInArrears(Builder $query): void
    {
        $query->where('days_past_due', '>', 0);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeByParBucket(Builder $query, string $bucket): void
    {
        $query->where('par_bucket', $bucket);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeByOfficer(Builder $query, int $officerId): void
    {
        $query->where('loan_officer_id', $officerId);
    }

    // ─── Business Logic ─────────────────────────────────

    /**
     * FR-LM-032: Compute the PAR bucket label from days past due.
     */
    public static function computeParBucket(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue <= 0 => 'current',
            $daysPastDue <= 30 => '1-30',
            $daysPastDue <= 60 => '31-60',
            $daysPastDue <= 90 => '61-90',
            $daysPastDue <= 180 => '91-180',
            default => '180+',
        };
    }

    /**
     * Get the PAR bucket colour for Filament badges.
     */
    public function getParBucketColour(): string
    {
        return match ($this->par_bucket) {
            'current' => 'success',
            '1-30' => 'warning',
            '31-60' => 'warning',
            '61-90' => 'danger',
            '91-180' => 'danger',
            '180+' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Refresh the total_outstanding computed column.
     */
    public function recalculateTotalOutstanding(): void
    {
        $total = (float) $this->outstanding_principal
            + (float) $this->outstanding_interest
            + (float) $this->outstanding_penalty;

        $this->update(['total_outstanding' => $total]);
    }
}
