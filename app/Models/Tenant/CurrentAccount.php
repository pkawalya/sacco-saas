<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Current account with overdraft and deposit insurance.
 *
 * @property int $id
 * @property string $account_number
 * @property float $ledger_balance
 * @property float $available_balance
 * @property float $overdraft_limit
 * @property string $status
 */
class CurrentAccount extends Model
{
    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_BUSINESS = 'business';

    public const TYPE_CORPORATE = 'corporate';

    public const TYPES = [
        self::TYPE_INDIVIDUAL => 'Individual',
        self::TYPE_BUSINESS => 'Business',
        self::TYPE_CORPORATE => 'Corporate',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'dormant' => 'Dormant',
        'frozen' => 'Frozen',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'account_number',
        'member_id',
        'account_holder',
        'account_type',
        'ledger_balance',
        'available_balance',
        'overdraft_limit',
        'minimum_balance',
        'cheque_book_issued',
        'debit_card_linked',
        'internet_banking',
        'mobile_banking',
        'monthly_fee',
        'transaction_fee',
        'deposit_insured',
        'insured_amount',
        'currency',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ledger_balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'overdraft_limit' => 'decimal:2',
            'minimum_balance' => 'decimal:2',
            'monthly_fee' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
            'insured_amount' => 'decimal:2',
            'cheque_book_issued' => 'boolean',
            'debit_card_linked' => 'boolean',
            'internet_banking' => 'boolean',
            'mobile_banking' => 'boolean',
            'deposit_insured' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Available balance includes overdraft.
     */
    public function getEffectiveAvailableAttribute(): float
    {
        return round((float) $this->available_balance + (float) $this->overdraft_limit, 2);
    }

    public function hasSufficientFunds(float $amount): bool
    {
        return $this->effective_available >= $amount;
    }
}
