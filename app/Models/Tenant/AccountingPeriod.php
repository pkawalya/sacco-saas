<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_LOCKED => 'Locked',
    ];

    protected $fillable = [
        'period_name',
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'close_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'period_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN);
    }

    // ─── Business Logic ─────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * FR-GL-003: Close the period with audit trail.
     */
    public function closePeriod(int $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_by' => $userId,
            'closed_at' => now(),
            'close_notes' => $notes,
        ]);
    }

    /**
     * FR-GL-003: Reopen a closed period with audit trail.
     */
    public function reopenPeriod(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'reopened_by' => $userId,
            'reopened_at' => now(),
        ]);
    }
}
