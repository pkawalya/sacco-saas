<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Interbank settlement (EFT/RTGS/cheque clearing).
 *
 * @property int $id
 * @property string $settlement_ref
 * @property string $settlement_type
 * @property float $amount
 * @property string $status
 */
class InterbankSettlement extends Model
{
    public const TYPE_EFT = 'eft';

    public const TYPE_RTGS = 'rtgs';

    public const TYPE_MOBILE_MONEY = 'mobile_money';

    public const TYPE_CHEQUE = 'cheque_clearing';

    public const TYPES = [
        self::TYPE_EFT => 'EFT',
        self::TYPE_RTGS => 'RTGS',
        self::TYPE_MOBILE_MONEY => 'Mobile Money',
        self::TYPE_CHEQUE => 'Cheque Clearing',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'settled' => 'Settled',
        'failed' => 'Failed',
        'reversed' => 'Reversed',
    ];

    protected $fillable = [
        'settlement_ref',
        'settlement_type',
        'originating_bank',
        'originating_account',
        'receiving_bank',
        'receiving_account',
        'amount',
        'currency',
        'fee',
        'value_date',
        'initiated_at',
        'settled_at',
        'status',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'value_date' => 'date',
            'initiated_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function markSettled(): void
    {
        $this->update([
            'status' => 'settled',
            'settled_at' => now(),
        ]);
    }
}
