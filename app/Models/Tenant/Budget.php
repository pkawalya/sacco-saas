<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Budget line with 3-tier tracking and variance controls.
 *
 * FR-RE-020–024: Budget management with variance reporting.
 *
 * @property int $id
 * @property string $budget_code
 * @property string $budget_name
 * @property string|null $description
 * @property int $gl_account_id
 * @property string|null $cost_centre_code
 * @property int $fiscal_year
 * @property string $period
 * @property string $start_date
 * @property string $end_date
 * @property float $original_amount
 * @property float $revised_amount
 * @property float $approved_amount
 * @property float $actual_amount
 * @property string $status
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $approval_notes
 * @property float $variance_threshold_pct
 * @property bool $enforce_budget
 */
class Budget extends Model
{
    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_CLOSED => 'Closed',
    ];

    // ─── Period Constants ───────────────────────────────────────
    public const PERIOD_ANNUAL = 'annual';

    public const PERIOD_Q1 = 'q1';

    public const PERIOD_Q2 = 'q2';

    public const PERIOD_Q3 = 'q3';

    public const PERIOD_Q4 = 'q4';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIODS = [
        self::PERIOD_ANNUAL => 'Annual',
        self::PERIOD_Q1 => 'Q1',
        self::PERIOD_Q2 => 'Q2',
        self::PERIOD_Q3 => 'Q3',
        self::PERIOD_Q4 => 'Q4',
        self::PERIOD_MONTHLY => 'Monthly',
    ];

    protected $fillable = [
        'budget_code',
        'budget_name',
        'description',
        'gl_account_id',
        'cost_centre_code',
        'fiscal_year',
        'period',
        'start_date',
        'end_date',
        'original_amount',
        'revised_amount',
        'approved_amount',
        'actual_amount',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'variance_threshold_pct',
        'enforce_budget',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'revised_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'variance_threshold_pct' => 'decimal:2',
            'enforce_budget' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class);
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
        $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_ACTIVE]);
    }

    // ─── Variance Helpers (FR-RE-020–024) ───────────────────────

    /**
     * Compute the variance (approved - actual).
     * Positive = under budget, negative = over budget.
     */
    public function getVarianceAttribute(): float
    {
        return round((float) $this->approved_amount - (float) $this->actual_amount, 2);
    }

    /**
     * Compute the variance percentage.
     */
    public function getVariancePercentageAttribute(): float
    {
        if ((float) $this->approved_amount === 0.0) {
            return 0.0;
        }

        return round(((float) $this->actual_amount / (float) $this->approved_amount) * 100, 2);
    }

    /**
     * Compute the utilisation percentage.
     */
    public function getUtilisationAttribute(): float
    {
        return $this->variance_percentage;
    }

    /**
     * Whether the budget is over the variance threshold.
     */
    public function isOverThreshold(): bool
    {
        return $this->variance_percentage > (100 + (float) $this->variance_threshold_pct);
    }

    /**
     * Remaining available budget.
     */
    public function getRemainingAttribute(): float
    {
        return max(0, round((float) $this->approved_amount - (float) $this->actual_amount, 2));
    }

    /**
     * Check if a proposed amount would exceed the budget.
     */
    public function wouldExceedBudget(float $proposedAmount): bool
    {
        return ((float) $this->actual_amount + $proposedAmount) > (float) $this->approved_amount;
    }

    /**
     * Record actual spend against this budget.
     */
    public function recordActual(float $amount): void
    {
        $this->increment('actual_amount', $amount);
    }
}
