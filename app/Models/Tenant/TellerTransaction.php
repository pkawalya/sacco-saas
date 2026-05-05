<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Teller transaction with limit-based approvals (FR-CH-001–003).
 *
 * @property int $id
 * @property string $transaction_ref
 * @property int $shift_id
 * @property string $transaction_type
 * @property float $amount
 * @property string $status
 */
class TellerTransaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_REVERSAL = 'reversal';

    public const TYPES = [
        self::TYPE_DEPOSIT => 'Deposit',
        self::TYPE_WITHDRAWAL => 'Withdrawal',
        self::TYPE_TRANSFER_IN => 'Transfer In',
        self::TYPE_TRANSFER_OUT => 'Transfer Out',
        self::TYPE_REVERSAL => 'Reversal',
    ];

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_REVERSED => 'Reversed',
        self::STATUS_PENDING => 'Pending Approval',
    ];

    protected $fillable = [
        'transaction_ref',
        'shift_id',
        'transaction_type',
        'teller_id',
        'teller_name',
        'member_id',
        'member_name',
        'account_number',
        'amount',
        'currency',
        'counterpart_teller_id',
        'counterpart_branch',
        'status',
        'requires_approval',
        'approved_by',
        'narration',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requires_approval' => 'boolean',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(TellerShift::class, 'shift_id');
    }
}
