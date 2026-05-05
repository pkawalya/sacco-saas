<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $journal_number
 * @property string $journal_type
 * @property string $status
 */
class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_SYSTEM = 'system';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_AUTO_REVERSAL = 'auto_reversal';

    public const TYPES = [
        self::TYPE_SYSTEM => 'System Generated',
        self::TYPE_MANUAL => 'Manual',
        self::TYPE_AUTO_REVERSAL => 'Auto-Reversal',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_POSTED => 'Posted',
        self::STATUS_REVERSED => 'Reversed',
        self::STATUS_VOID => 'Void',
    ];

    protected $fillable = [
        'journal_number',
        'journal_type',
        'transaction_date',
        'value_date',
        'description',
        'source_module',
        'source_reference',
        'source_id',
        'period_id',
        'total_debit',
        'total_credit',
        'currency_code',
        'is_reversal',
        'reversal_of_id',
        'auto_reverse_date',
        'status',
        'created_by',
        'approved_by',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'value_date' => 'date',
            'auto_reverse_date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'is_reversal' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopePosted(Builder $query): void
    {
        $query->where('status', self::STATUS_POSTED);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('journal_type', $type);
    }

    // ─── Helpers ────────────────────────────────────────

    /**
     * Check if this journal entry is balanced (debits == credits).
     */
    public function isBalanced(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.01;
    }

    /**
     * Recalculate totals from lines.
     */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_debit' => $this->lines()->sum('debit'),
            'total_credit' => $this->lines()->sum('credit'),
        ]);
    }
}
