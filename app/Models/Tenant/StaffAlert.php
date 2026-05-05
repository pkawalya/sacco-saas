<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff alert with acknowledgement and escalation (FR-AN-020–022).
 *
 * @property int $id
 * @property string $alert_id
 * @property string $event_type
 * @property string $title
 * @property string $message
 * @property string $severity
 * @property int $recipient_id
 * @property string $status
 * @property bool $is_escalated
 * @property int $escalation_tier
 */
class StaffAlert extends Model
{
    // ─── Severity ───────────────────────────────────────────────
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_INFO => 'Info',
        self::SEVERITY_WARNING => 'Warning',
        self::SEVERITY_CRITICAL => 'Critical',
    ];

    // ─── Status ─────────────────────────────────────────────────
    public const STATUS_UNREAD = 'unread';

    public const STATUS_READ = 'read';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUSES = [
        self::STATUS_UNREAD => 'Unread',
        self::STATUS_READ => 'Read',
        self::STATUS_ACKNOWLEDGED => 'Acknowledged',
        self::STATUS_ESCALATED => 'Escalated',
    ];

    protected $fillable = [
        'alert_id',
        'event_type',
        'title',
        'message',
        'severity',
        'recipient_id',
        'recipient_name',
        'recipient_role',
        'status',
        'read_at',
        'acknowledged_at',
        'is_escalated',
        'escalation_tier',
        'escalated_at',
        'escalated_to',
        'context',
        'source_module',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_escalated' => 'boolean',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'escalated_at' => 'datetime',
            'context' => 'array',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUnacknowledged(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_ACKNOWLEDGED]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForRecipient(Builder $query, int $recipientId): void
    {
        $query->where('recipient_id', $recipientId);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCritical(Builder $query): void
    {
        $query->where('severity', self::SEVERITY_CRITICAL);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function markRead(): void
    {
        if ($this->status === self::STATUS_UNREAD) {
            $this->update([
                'status' => self::STATUS_READ,
                'read_at' => now(),
            ]);
        }
    }

    public function acknowledge(): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Check if alert should be auto-escalated based on elapsed time.
     */
    public function shouldEscalate(int $thresholdMinutes): bool
    {
        return in_array($this->status, [self::STATUS_UNREAD, self::STATUS_READ])
            && $this->created_at->diffInMinutes(now()) >= $thresholdMinutes;
    }

    public function escalate(int $escalatedTo, int $newTier): void
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
            'is_escalated' => true,
            'escalation_tier' => $newTier,
            'escalated_at' => now(),
            'escalated_to' => $escalatedTo,
        ]);
    }
}
