<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $application_ref
 * @property string $status
 * @property float $amount_requested
 * @property float|null $dscr
 */
class LoanApplication extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_WITHDRAWN => 'Withdrawn',
    ];

    protected $fillable = [
        'application_ref',
        'member_id',
        'product_id',
        'amount_requested',
        'tenure_months_requested',
        'purpose',
        'purpose_details',
        'monthly_income',
        'monthly_expenses',
        'dscr',
        'dscr_passed',
        'amount_recommended',
        'tenure_months_recommended',
        'recommended_by',
        'officer_notes',
        'status',
        'branch_code',
        'submitted_by',
        'submitted_at',
        'decision_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_requested' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'monthly_expenses' => 'decimal:2',
            'dscr' => 'decimal:4',
            'amount_recommended' => 'decimal:2',
            'dscr_passed' => 'boolean',
            'submitted_at' => 'datetime',
            'decision_at' => 'datetime',
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

    public function loan(): HasMany
    {
        return $this->hasMany(Loan::class, 'application_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    // ─── Business Logic ─────────────────────────────────

    /**
     * FR-LM-010: Compute Debt Service Coverage Ratio.
     *
     * DSCR = Net Income / Total Debt Service
     * Net Income = Monthly Income − Monthly Expenses
     * Total Debt Service = Proposed Instalment (estimated) + Existing Obligations
     *
     * A DSCR ≥ 1.25 is typically required.
     */
    public function computeDscr(float $proposedMonthlyInstalment, float $existingMonthlyObligations = 0): float
    {
        $income = (float) $this->monthly_income;
        $expenses = (float) $this->monthly_expenses;

        $netIncome = $income - $expenses;
        $totalDebtService = $proposedMonthlyInstalment + $existingMonthlyObligations;

        if ($totalDebtService <= 0) {
            return 0.0;
        }

        return round($netIncome / $totalDebtService, 4);
    }
}
