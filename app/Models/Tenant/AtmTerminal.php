<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * ATM terminal with cash monitoring.
 *
 * @property int $id
 * @property string $terminal_id
 * @property float $current_cash
 * @property string $status
 */
class AtmTerminal extends Model
{
    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_OUT_OF_CASH = 'out_of_cash';

    public const STATUSES = [
        self::STATUS_ONLINE => 'Online',
        self::STATUS_OFFLINE => 'Offline',
        self::STATUS_MAINTENANCE => 'Maintenance',
        self::STATUS_OUT_OF_CASH => 'Out of Cash',
    ];

    protected $fillable = [
        'terminal_id',
        'terminal_name',
        'location',
        'branch_code',
        'current_cash',
        'max_cash',
        'min_cash_alert',
        'total_transactions_today',
        'status',
        'last_replenished_at',
        'last_transaction_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_cash' => 'decimal:2',
            'max_cash' => 'decimal:2',
            'min_cash_alert' => 'decimal:2',
            'last_replenished_at' => 'datetime',
            'last_transaction_at' => 'datetime',
        ];
    }

    public function needsReplenishment(): bool
    {
        return (float) $this->current_cash <= (float) $this->min_cash_alert;
    }

    public function replenish(float $amount): void
    {
        $this->update([
            'current_cash' => (float) $this->current_cash + $amount,
            'status' => self::STATUS_ONLINE,
            'last_replenished_at' => now(),
        ]);
    }
}
