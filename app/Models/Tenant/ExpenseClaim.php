<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Expense claim with workflow (draft → submitted → approved → paid).
 *
 * FR-RE-020–024: Expense claims with budget linkage.
 *
 * @property int $id
 * @property string $claim_number
 * @property string $claimant_name
 * @property int|null $claimant_user_id
 * @property string $category
 * @property int $gl_account_id
 * @property string|null $cost_centre_code
 * @property float $claimed_amount
 * @property float|null $approved_amount
 * @property string $currency
 * @property string $description
 * @property string $expense_date
 * @property string|null $receipt_path
 * @property array|null $line_items
 * @property string $status
 * @property int|null $reviewed_by
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 * @property int|null $budget_id
 */
class ExpenseClaim extends Model
{
    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_PAID => 'Paid',
    ];

    // ─── Category Constants ─────────────────────────────────────
    public const CATEGORY_TRAVEL = 'travel';

    public const CATEGORY_TRAINING = 'training';

    public const CATEGORY_SUPPLIES = 'supplies';

    public const CATEGORY_UTILITIES = 'utilities';

    public const CATEGORY_TELEPHONE = 'telephone';

    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_TRAVEL => 'Travel & Transport',
        self::CATEGORY_TRAINING => 'Training & Development',
        self::CATEGORY_SUPPLIES => 'Office Supplies',
        self::CATEGORY_UTILITIES => 'Utilities',
        self::CATEGORY_TELEPHONE => 'Telephone & Internet',
        self::CATEGORY_OTHER => 'Other',
    ];

    protected $fillable = [
        'claim_number',
        'claimant_name',
        'claimant_user_id',
        'category',
        'gl_account_id',
        'cost_centre_code',
        'claimed_amount',
        'approved_amount',
        'currency',
        'description',
        'expense_date',
        'receipt_path',
        'line_items',
        'status',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'paid_at',
        'budget_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claimed_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'line_items' => 'array',
            'expense_date' => 'date',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    // ─── Workflow Helpers ────────────────────────────────────────

    /**
     * Submit the claim for approval.
     */
    public function submit(): void
    {
        $this->update(['status' => self::STATUS_SUBMITTED]);
    }

    /**
     * Approve the claim with an approved amount.
     *
     * @throws \RuntimeException
     */
    public function approve(int $approverId, ?float $approvedAmount = null, ?string $notes = null): void
    {
        if ($this->status !== self::STATUS_SUBMITTED && $this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \RuntimeException("Cannot approve claim in '{$this->status}' status.");
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approved_amount' => $approvedAmount ?? $this->claimed_amount,
        ]);

        // Record against budget if linked
        if ($this->budget_id) {
            $this->budget->recordActual($this->approved_amount);
        }
    }

    /**
     * Reject the claim.
     *
     * @throws \RuntimeException
     */
    public function reject(int $reviewerId, string $reason): void
    {
        if ($this->status !== self::STATUS_SUBMITTED && $this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \RuntimeException("Cannot reject claim in '{$this->status}' status.");
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark the claim as paid.
     *
     * @throws \RuntimeException
     */
    public function markPaid(): void
    {
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \RuntimeException('Cannot mark as paid — claim is not approved.');
        }

        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
