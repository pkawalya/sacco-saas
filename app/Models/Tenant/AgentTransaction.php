<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agent transaction with float tracking (FR-CH-030–031).
 *
 * @property int $id
 * @property string $transaction_ref
 * @property int $agent_id
 * @property string $transaction_type
 * @property float $amount
 * @property float $commission_amount
 */
class AgentTransaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_FLOAT_TOP_UP = 'float_top_up';

    public const TYPE_FLOAT_DEDUCTION = 'float_deduction';

    public const TYPE_COMMISSION = 'commission';

    public const TYPES = [
        self::TYPE_DEPOSIT => 'Deposit',
        self::TYPE_WITHDRAWAL => 'Withdrawal',
        self::TYPE_FLOAT_TOP_UP => 'Float Top-up',
        self::TYPE_FLOAT_DEDUCTION => 'Float Deduction',
        self::TYPE_COMMISSION => 'Commission',
    ];

    protected $fillable = [
        'transaction_ref',
        'agent_id',
        'transaction_type',
        'member_id',
        'member_name',
        'amount',
        'commission_amount',
        'float_before',
        'float_after',
        'status',
        'narration',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'float_before' => 'decimal:2',
            'float_after' => 'decimal:2',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
