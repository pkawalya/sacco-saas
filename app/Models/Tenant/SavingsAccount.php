<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $account_number
 * @property string $status
 * @property float $ledger_balance
 * @property float $available_balance
 * @property float $held_amount
 */
class SavingsAccount extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DORMANT = 'dormant';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_DORMANT => 'Dormant',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    protected $fillable = [
        'account_number',
        'member_id',
        'product_id',
        'ledger_balance',
        'available_balance',
        'held_amount',
        'accrued_interest',
        'is_joint',
        'mandate_type',
        'joint_member_ids',
        'status',
        'branch_code',
        'opened_by',
        'opened_date',
        'closed_date',
        'closure_reason',
        'last_transaction_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ledger_balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'held_amount' => 'decimal:2',
            'accrued_interest' => 'decimal:2',
            'is_joint' => 'boolean',
            'joint_member_ids' => 'array',
            'opened_date' => 'date',
            'closed_date' => 'date',
            'last_transaction_date' => 'date',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class, 'account_id')->orderByDesc('posted_at');
    }

    public function interestAccruals(): HasMany
    {
        return $this->hasMany(InterestAccrual::class, 'account_id');
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
    public function scopeByBranch(Builder $query, string $branchCode): void
    {
        $query->where('branch_code', $branchCode);
    }

    // ─── Business Logic ─────────────────────────────────

    /**
     * FR-SD-002: Check whether a withdrawal would breach minimum balance.
     */
    public function wouldBreachMinimumBalance(float $withdrawalAmount): bool
    {
        $minimumBalance = (float) $this->product->minimum_balance;
        $projectedBalance = (float) $this->available_balance - $withdrawalAmount;

        return $projectedBalance < $minimumBalance;
    }

    /**
     * Apply a hold on the account (e.g., for guarantor locking).
     */
    public function applyHold(float $amount): void
    {
        $this->increment('held_amount', $amount);
        $this->decrement('available_balance', $amount);
    }

    /**
     * Release a hold from the account.
     */
    public function releaseHold(float $amount): void
    {
        $releaseAmount = min($amount, (float) $this->held_amount);
        $this->decrement('held_amount', $releaseAmount);
        $this->increment('available_balance', $releaseAmount);
    }
}
