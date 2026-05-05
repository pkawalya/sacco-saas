<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $fd_number
 * @property string $status
 * @property float $principal_amount
 * @property Carbon $maturity_date
 */
class FixedDeposit extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MATURED = 'matured';

    public const STATUS_ROLLED_OVER = 'rolled_over';

    public const STATUS_TERMINATED = 'terminated';

    public const STATUS_BROKEN = 'broken';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_MATURED => 'Matured',
        self::STATUS_ROLLED_OVER => 'Rolled Over',
        self::STATUS_TERMINATED => 'Terminated',
        self::STATUS_BROKEN => 'Broken (Early Withdrawal)',
    ];

    public const ROLLOVER_PRINCIPAL_ONLY = 'principal_only';

    public const ROLLOVER_PRINCIPAL_AND_INTEREST = 'principal_and_interest';

    protected $fillable = [
        'fd_number',
        'member_id',
        'product_id',
        'funding_account_id',
        'principal_amount',
        'interest_rate',
        'interest_earned',
        'maturity_amount',
        'start_date',
        'maturity_date',
        'tenure_months',
        'auto_rollover',
        'rollover_type',
        'rollover_count',
        'rolled_from_id',
        'is_broken',
        'broken_date',
        'early_withdrawal_penalty',
        'status',
        'branch_code',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'interest_earned' => 'decimal:2',
            'maturity_amount' => 'decimal:2',
            'early_withdrawal_penalty' => 'decimal:2',
            'start_date' => 'date',
            'maturity_date' => 'date',
            'broken_date' => 'date',
            'auto_rollover' => 'boolean',
            'is_broken' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SavingsProduct::class, 'product_id');
    }

    public function fundingAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'funding_account_id');
    }

    public function rolledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rolled_from_id');
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
    public function scopeMaturingBefore(Builder $query, Carbon $date): void
    {
        $query->where('maturity_date', '<=', $date)->where('status', self::STATUS_ACTIVE);
    }

    // ─── Business Logic ─────────────────────────────────

    /**
     * FR-SD-022: Compute expected interest at maturity.
     */
    public function computeMaturityAmount(): float
    {
        $principal = (float) $this->principal_amount;
        $rate = (float) $this->interest_rate / 100;
        $months = (int) $this->tenure_months;

        $interest = $principal * $rate * ($months / 12);
        $total = $principal + $interest;

        return round($total, 2);
    }

    /**
     * FR-SD-022: Compute early withdrawal penalty.
     */
    public function computeEarlyWithdrawalPenalty(): float
    {
        $penaltyRate = (float) ($this->product->early_withdrawal_penalty_rate ?? 0);

        if ($penaltyRate <= 0) {
            return 0.0;
        }

        $interestEarned = (float) $this->interest_earned;
        $penalty = $interestEarned * ($penaltyRate / 100);

        return round($penalty, 2);
    }

    public function isDueForMaturity(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->maturity_date->isPast();
    }
}
