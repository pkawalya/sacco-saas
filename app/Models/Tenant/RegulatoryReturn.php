<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Regulatory return with auto-generation, deadline tracking, filing register.
 *
 * FR-RC-001: Auto-generation
 * FR-RC-002: Deadline tracking
 * FR-RC-003: Filing register
 *
 * @property int $id
 * @property string $return_code
 * @property string $return_name
 * @property string $return_type
 * @property string $period
 * @property int $fiscal_year
 * @property int|null $period_number
 * @property string $due_date
 * @property string $status
 */
class RegulatoryReturn extends Model
{
    protected $table = 'regulatory_returns';

    // ─── Type Constants ─────────────────────────────────────────
    public const TYPE_PRUDENTIAL = 'prudential';

    public const TYPE_STATISTICAL = 'statistical';

    public const TYPE_AML = 'aml';

    public const TYPE_TAX = 'tax';

    public const TYPE_CRB = 'crb';

    public const TYPES = [
        self::TYPE_PRUDENTIAL => 'Prudential',
        self::TYPE_STATISTICAL => 'Statistical',
        self::TYPE_AML => 'AML',
        self::TYPE_TAX => 'Tax',
        self::TYPE_CRB => 'CRB',
    ];

    // ─── Period Constants ───────────────────────────────────────
    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_QUARTERLY = 'quarterly';

    public const PERIOD_ANNUAL = 'annual';

    public const PERIODS = [
        self::PERIOD_MONTHLY => 'Monthly',
        self::PERIOD_QUARTERLY => 'Quarterly',
        self::PERIOD_ANNUAL => 'Annual',
    ];

    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_OVERDUE => 'Overdue',
    ];

    protected $fillable = [
        'return_code',
        'return_name',
        'return_type',
        'period',
        'fiscal_year',
        'period_number',
        'period_start',
        'period_end',
        'due_date',
        'reminder_days_before',
        'status',
        'filed_date',
        'filing_reference',
        'filed_by',
        'approved_by',
        'return_data',
        'notes',
        'attachment_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'filed_date' => 'date',
            'return_data' => 'array',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_SUBMITTED)
            ->where('status', '!=', self::STATUS_ACCEPTED)
            ->whereDate('due_date', '<', now()->toDateString());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeDueSoon(Builder $query, int $withinDays = 7): void
    {
        $query->where('status', self::STATUS_PENDING)
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($withinDays)->toDateString()]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('return_type', $type);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return ! in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_ACCEPTED])
            && $this->due_date->isPast();
    }

    public function getDaysUntilDueAttribute(): int
    {
        return max(0, (int) now()->diffInDays($this->due_date, false));
    }

    public function isReminderDue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->days_until_due <= $this->reminder_days_before;
    }

    public function markSubmitted(int $userId, ?string $reference = null): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'filed_date' => now()->toDateString(),
            'filed_by' => $userId,
            'filing_reference' => $reference,
        ]);
    }
}
