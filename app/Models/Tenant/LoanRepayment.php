<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $amount_paid
 * @property float $allocated_to_penalty
 * @property float $allocated_to_interest
 * @property float $allocated_to_principal
 * @property bool $is_reversed
 */
class LoanRepayment extends Model
{
    use HasFactory;

    public const CHANNEL_BRANCH = 'branch';

    public const CHANNEL_MOBILE = 'mobile';

    public const CHANNEL_USSD = 'ussd';

    public const CHANNEL_AGENT = 'agent';

    public const CHANNEL_PAYROLL = 'payroll';

    public const CHANNEL_STANDING_ORDER = 'standing_order';

    public const CHANNELS = [
        self::CHANNEL_BRANCH => 'Branch (Cash)',
        self::CHANNEL_MOBILE => 'Mobile Money',
        self::CHANNEL_USSD => 'USSD',
        self::CHANNEL_AGENT => 'Agent',
        self::CHANNEL_PAYROLL => 'Payroll Deduction',
        self::CHANNEL_STANDING_ORDER => 'Standing Order',
    ];

    protected $fillable = [
        'receipt_number',
        'loan_id',
        'member_id',
        'amount_paid',
        'channel',
        'reference_number',
        'allocated_to_penalty',
        'allocated_to_interest',
        'allocated_to_principal',
        'allocated_to_fees',
        'excess_amount',
        'is_reversed',
        'reversal_of',
        'reversal_reason',
        'outstanding_after',
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
            'amount_paid' => 'decimal:2',
            'allocated_to_penalty' => 'decimal:2',
            'allocated_to_interest' => 'decimal:2',
            'allocated_to_principal' => 'decimal:2',
            'allocated_to_fees' => 'decimal:2',
            'excess_amount' => 'decimal:2',
            'outstanding_after' => 'decimal:2',
            'is_reversed' => 'boolean',
            'value_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of');
    }
}
