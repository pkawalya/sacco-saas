<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Offline sync queue entry (FR-CH-040–042).
 *
 * @property int $id
 * @property string $branch_code
 * @property int $transaction_count
 * @property array $payload
 * @property string $sync_status
 * @property int $retry_count
 */
class OfflineSyncQueue extends Model
{
    protected $table = 'offline_sync_queue';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCING = 'syncing';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_CONFLICT = 'conflict';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_SYNCING => 'Syncing',
        self::STATUS_SYNCED => 'Synced',
        self::STATUS_CONFLICT => 'Conflict',
        self::STATUS_FAILED => 'Failed',
    ];

    public const RESOLUTION_SERVER_WINS = 'server_wins';

    public const RESOLUTION_CLIENT_WINS = 'client_wins';

    public const RESOLUTION_MANUAL = 'manual';

    protected $fillable = [
        'branch_code',
        'device_id',
        'transaction_count',
        'payload',
        'sync_status',
        'retry_count',
        'synced_at',
        'conflicts',
        'resolution_strategy',
        'resolved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'conflicts' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('sync_status', self::STATUS_PENDING);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithConflicts(Builder $query): void
    {
        $query->where('sync_status', self::STATUS_CONFLICT);
    }

    public function markSynced(): void
    {
        $this->update([
            'sync_status' => self::STATUS_SYNCED,
            'synced_at' => now(),
        ]);
    }

    public function markConflict(array $conflicts): void
    {
        $this->update([
            'sync_status' => self::STATUS_CONFLICT,
            'conflicts' => $conflicts,
        ]);
    }

    public function retry(): void
    {
        $this->increment('retry_count');
        $this->update(['sync_status' => self::STATUS_PENDING]);
    }
}
