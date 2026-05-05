<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Teller shift with cash management and transaction limits (FR-CH-001–004).
 *
 * @property int $id
 * @property string $shift_number
 * @property int $teller_id
 * @property string $teller_name
 * @property string $branch_code
 * @property float $opening_balance
 * @property float|null $closing_balance
 * @property string $status
 */
class TellerShift extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    protected $fillable = [
        'shift_number',
        'teller_id',
        'teller_name',
        'branch_code',
        'branch_name',
        'opening_balance',
        'closing_balance',
        'total_deposits',
        'total_withdrawals',
        'total_transfers_in',
        'total_transfers_out',
        'deposit_limit',
        'withdrawal_limit',
        'daily_cash_limit',
        'opened_at',
        'closed_at',
        'status',
        'closing_notes',
        'variance',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'total_deposits' => 'decimal:2',
            'total_withdrawals' => 'decimal:2',
            'total_transfers_in' => 'decimal:2',
            'total_transfers_out' => 'decimal:2',
            'deposit_limit' => 'decimal:2',
            'withdrawal_limit' => 'decimal:2',
            'daily_cash_limit' => 'decimal:2',
            'variance' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TellerTransaction::class, 'shift_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForBranch(Builder $query, string $branchCode): void
    {
        $query->where('branch_code', $branchCode);
    }

    /**
     * Computed expected closing balance.
     */
    public function getExpectedBalanceAttribute(): float
    {
        return round(
            (float) $this->opening_balance
            + (float) $this->total_deposits
            - (float) $this->total_withdrawals
            + (float) $this->total_transfers_in
            - (float) $this->total_transfers_out,
            2
        );
    }

    /**
     * Check if a transaction exceeds limit (FR-CH-002).
     */
    public function exceedsLimit(string $type, float $amount): bool
    {
        return match ($type) {
            TellerTransaction::TYPE_DEPOSIT => $amount > (float) $this->deposit_limit,
            TellerTransaction::TYPE_WITHDRAWAL => $amount > (float) $this->withdrawal_limit,
            default => false,
        };
    }

    /**
     * Close the shift with actual cash count (FR-CH-004).
     */
    public function closeShift(float $actualBalance, ?string $notes = null): void
    {
        $expected = $this->expected_balance;

        $this->update([
            'status' => self::STATUS_CLOSED,
            'closing_balance' => $actualBalance,
            'variance' => round($actualBalance - $expected, 2),
            'closed_at' => now(),
            'closing_notes' => $notes,
        ]);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
