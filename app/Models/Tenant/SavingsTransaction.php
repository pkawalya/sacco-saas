<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $transaction_ref
 * @property string $transaction_type
 * @property float $amount
 * @property string $channel
 */
class SavingsTransaction extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_INTEREST_CREDIT = 'interest_credit';

    public const TYPE_PENALTY_DEBIT = 'penalty_debit';

    public const TYPE_REVERSAL = 'reversal';

    public const TYPES = [
        self::TYPE_DEPOSIT => 'Deposit',
        self::TYPE_WITHDRAWAL => 'Withdrawal',
        self::TYPE_TRANSFER_IN => 'Transfer In',
        self::TYPE_TRANSFER_OUT => 'Transfer Out',
        self::TYPE_INTEREST_CREDIT => 'Interest Credit',
        self::TYPE_PENALTY_DEBIT => 'Penalty Debit',
        self::TYPE_REVERSAL => 'Reversal',
    ];

    public const CHANNEL_BRANCH = 'branch';

    public const CHANNEL_MOBILE = 'mobile';

    public const CHANNEL_USSD = 'ussd';

    public const CHANNEL_AGENT = 'agent';

    public const CHANNEL_EFT = 'eft';

    public const CHANNEL_PAYROLL = 'payroll';

    public const CHANNEL_STANDING_ORDER = 'standing_order';

    public const CHANNEL_CHEQUE = 'cheque';

    public const CHANNELS = [
        self::CHANNEL_BRANCH => 'Branch (Cash)',
        self::CHANNEL_MOBILE => 'Mobile Money',
        self::CHANNEL_USSD => 'USSD',
        self::CHANNEL_AGENT => 'Agent',
        self::CHANNEL_EFT => 'EFT / Bank Transfer',
        self::CHANNEL_PAYROLL => 'Payroll Deduction',
        self::CHANNEL_STANDING_ORDER => 'Standing Order',
        self::CHANNEL_CHEQUE => 'Cheque',
    ];

    /**
     * Credit transaction types (money coming in).
     */
    public const CREDIT_TYPES = [
        self::TYPE_DEPOSIT,
        self::TYPE_TRANSFER_IN,
        self::TYPE_INTEREST_CREDIT,
    ];

    /**
     * Debit transaction types (money going out).
     */
    public const DEBIT_TYPES = [
        self::TYPE_WITHDRAWAL,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_PENALTY_DEBIT,
    ];

    protected $fillable = [
        'transaction_ref',
        'account_id',
        'member_id',
        'transaction_type',
        'amount',
        'running_balance',
        'description',
        'channel',
        'reference_number',
        'counterpart_account_id',
        'is_reversed',
        'reversal_of',
        'processed_by',
        'value_date',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'is_reversed' => 'boolean',
            'value_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'account_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function counterpartAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'counterpart_account_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of');
    }

    // ─── Helpers ────────────────────────────────────────

    public function isCredit(): bool
    {
        return in_array($this->transaction_type, self::CREDIT_TYPES, true);
    }

    public function isDebit(): bool
    {
        return in_array($this->transaction_type, self::DEBIT_TYPES, true);
    }
}
