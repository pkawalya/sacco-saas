<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Promise to Pay record with auto-flag on broken promise (FR-CE-011).
 *
 * @property int $id
 * @property int $worklist_id
 * @property int $loan_id
 * @property string $loan_number
 * @property float $promised_amount
 * @property string $promised_date
 * @property float $actual_amount_paid
 * @property string|null $actual_payment_date
 * @property string $status
 * @property bool $is_broken
 */
class PtpRecord extends Model
{
    protected $table = 'ptp_records';

    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_PENDING = 'pending';

    public const STATUS_KEPT = 'kept';

    public const STATUS_BROKEN = 'broken';

    public const STATUS_PARTIAL = 'partial';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_KEPT => 'Kept',
        self::STATUS_BROKEN => 'Broken',
        self::STATUS_PARTIAL => 'Partial',
    ];

    protected $fillable = [
        'worklist_id',
        'loan_id',
        'loan_number',
        'promised_amount',
        'promised_date',
        'actual_amount_paid',
        'actual_payment_date',
        'status',
        'is_broken',
        'broken_flagged_at',
        'captured_by',
        'officer_name',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'promised_amount' => 'decimal:2',
            'actual_amount_paid' => 'decimal:2',
            'promised_date' => 'date',
            'actual_payment_date' => 'date',
            'is_broken' => 'boolean',
            'broken_flagged_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function worklist(): BelongsTo
    {
        return $this->belongsTo(CollectionsWorklist::class, 'worklist_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeBroken(Builder $query): void
    {
        $query->where('is_broken', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    // ─── Helpers (FR-CE-011) ────────────────────────────────────

    /**
     * Check if PTP is overdue and should be flagged as broken.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->promised_date->isPast();
    }

    /**
     * Flag as broken promise.
     */
    public function flagAsBroken(): void
    {
        $this->update([
            'status' => self::STATUS_BROKEN,
            'is_broken' => true,
            'broken_flagged_at' => now(),
        ]);
    }

    /**
     * Record payment against PTP.
     */
    public function recordPayment(float $amount, ?string $paymentDate = null): void
    {
        $amountPaid = (float) $this->actual_amount_paid + $amount;
        $promisedAmount = (float) $this->promised_amount;

        $newStatus = $amountPaid >= $promisedAmount
            ? self::STATUS_KEPT
            : self::STATUS_PARTIAL;

        $this->update([
            'actual_amount_paid' => $amountPaid,
            'actual_payment_date' => $paymentDate ?? now()->toDateString(),
            'status' => $newStatus,
            'is_broken' => false,
        ]);
    }
}
