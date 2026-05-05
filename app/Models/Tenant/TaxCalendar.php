<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tax compliance calendar entry (FR-RC-021–022).
 *
 * @property int $id
 * @property string $tax_type
 * @property string $description
 * @property int $fiscal_year
 * @property string $due_date
 * @property float $computed_amount
 * @property float $paid_amount
 * @property float $penalty_amount
 * @property string $filing_status
 */
class TaxCalendar extends Model
{
    protected $table = 'tax_calendar';

    // ─── Tax Types ──────────────────────────────────────────────
    public const TAX_PAYE = 'paye';

    public const TAX_VAT = 'vat';

    public const TAX_WHT = 'withholding_tax';

    public const TAX_CORPORATE = 'corporate_tax';

    public const TAX_EXCISE = 'excise_duty';

    public const TAX_TYPES = [
        self::TAX_PAYE => 'PAYE',
        self::TAX_VAT => 'VAT',
        self::TAX_WHT => 'Withholding Tax',
        self::TAX_CORPORATE => 'Corporate Tax',
        self::TAX_EXCISE => 'Excise Duty',
    ];

    // ─── Filing Status ──────────────────────────────────────────
    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_DUE = 'due';

    public const STATUS_FILED = 'filed';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const FILING_STATUSES = [
        self::STATUS_UPCOMING => 'Upcoming',
        self::STATUS_DUE => 'Due',
        self::STATUS_FILED => 'Filed',
        self::STATUS_PAID => 'Paid',
        self::STATUS_OVERDUE => 'Overdue',
    ];

    protected $fillable = [
        'tax_type',
        'description',
        'fiscal_year',
        'period_month',
        'period_start',
        'period_end',
        'due_date',
        'reminder_days_before',
        'computed_amount',
        'paid_amount',
        'penalty_amount',
        'filing_status',
        'filed_date',
        'payment_date',
        'receipt_number',
        'filed_by',
        'notes',
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
            'payment_date' => 'date',
            'computed_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotIn('filing_status', [self::STATUS_FILED, self::STATUS_PAID])
            ->whereDate('due_date', '<', now()->toDateString());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeDueSoon(Builder $query, int $days = 7): void
    {
        $query->whereIn('filing_status', [self::STATUS_UPCOMING, self::STATUS_DUE])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->computed_amount + (float) $this->penalty_amount - (float) $this->paid_amount, 2);
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->filing_status, [self::STATUS_FILED, self::STATUS_PAID])
            && $this->due_date->isPast();
    }

    public function markPaid(float $amount, string $receiptNumber): void
    {
        $this->update([
            'paid_amount' => (float) $this->paid_amount + $amount,
            'payment_date' => now()->toDateString(),
            'receipt_number' => $receiptNumber,
            'filing_status' => self::STATUS_PAID,
        ]);
    }
}
