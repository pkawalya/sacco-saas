<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Debit/prepaid card.
 *
 * @property int $id
 * @property string $card_number
 * @property string $masked_pan
 * @property string $card_type
 * @property string $status
 */
class Card extends Model
{
    public const TYPE_DEBIT = 'debit';

    public const TYPE_PREPAID = 'prepaid';

    public const TYPE_VIRTUAL = 'virtual';

    public const TYPES = [
        self::TYPE_DEBIT => 'Debit Card',
        self::TYPE_PREPAID => 'Prepaid Card',
        self::TYPE_VIRTUAL => 'Virtual Card',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'blocked' => 'Blocked',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'card_number',
        'masked_pan',
        'member_id',
        'cardholder_name',
        'card_type',
        'card_scheme',
        'daily_limit',
        'monthly_limit',
        'pos_limit',
        'atm_limit',
        'linked_account',
        'expiry_date',
        'status',
        'blocked_at',
        'block_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'pos_limit' => 'decimal:2',
            'atm_limit' => 'decimal:2',
            'expiry_date' => 'date',
            'blocked_at' => 'datetime',
        ];
    }

    public function block(string $reason): void
    {
        $this->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'block_reason' => $reason,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
