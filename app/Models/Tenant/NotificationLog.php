<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable notification audit log entry.
 *
 * FR-AN-041: Records are append-only; status can only progress forward.
 * FR-AN-001: Tracks failover channel and retry attempts.
 *
 * @property int $id
 * @property int|null $notification_template_id
 * @property string $recipient_type
 * @property int|null $recipient_id
 * @property string $recipient_identifier
 * @property string $channel
 * @property string $event_type
 * @property string $priority
 * @property string|null $subject
 * @property string $rendered_body
 * @property string $status
 * @property string|null $failover_channel
 * @property int $attempt_count
 * @property int $max_attempts
 * @property string|null $provider
 * @property string|null $external_id
 * @property string|null $error_message
 * @property string|null $source_module
 * @property string|null $source_reference
 * @property int|null $source_id
 */
class NotificationLog extends Model
{
    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_QUEUED => 'Queued',
        self::STATUS_SENT => 'Sent',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_BOUNCED => 'Bounced',
    ];

    /**
     * Valid forward status transitions (for immutability enforcement).
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_QUEUED, self::STATUS_SENT, self::STATUS_FAILED],
        self::STATUS_QUEUED => [self::STATUS_SENT, self::STATUS_FAILED],
        self::STATUS_SENT => [self::STATUS_DELIVERED, self::STATUS_BOUNCED, self::STATUS_FAILED],
        self::STATUS_DELIVERED => [],
        self::STATUS_FAILED => [self::STATUS_QUEUED, self::STATUS_SENT],
        self::STATUS_BOUNCED => [],
    ];

    // ─── Recipient Type Constants ───────────────────────────────
    public const RECIPIENT_MEMBER = 'member';

    public const RECIPIENT_STAFF = 'staff';

    public const RECIPIENT_EXTERNAL = 'external';

    public const RECIPIENT_TYPES = [
        self::RECIPIENT_MEMBER => 'Member',
        self::RECIPIENT_STAFF => 'Staff',
        self::RECIPIENT_EXTERNAL => 'External',
    ];

    protected $table = 'notification_log';

    protected $fillable = [
        'notification_template_id',
        'recipient_type',
        'recipient_id',
        'recipient_identifier',
        'channel',
        'event_type',
        'priority',
        'subject',
        'rendered_body',
        'status',
        'failover_channel',
        'attempt_count',
        'max_attempts',
        'provider',
        'external_id',
        'error_message',
        'source_module',
        'source_reference',
        'source_id',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'recipient_id' => 'integer',
            'source_id' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    // ─── Status Helpers ─────────────────────────────────────────

    /**
     * Check if a status transition is valid (forward-only).
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Transition the log entry to a new status with timestamp.
     *
     * @throws \RuntimeException If the transition is invalid
     */
    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Cannot transition notification log #{$this->id} from '{$this->status}' to '{$newStatus}'."
            );
        }

        $this->status = $newStatus;

        match ($newStatus) {
            self::STATUS_QUEUED => $this->queued_at = now(),
            self::STATUS_SENT => $this->sent_at = now(),
            self::STATUS_DELIVERED => $this->delivered_at = now(),
            self::STATUS_FAILED, self::STATUS_BOUNCED => $this->failed_at = now(),
            default => null,
        };

        $this->save();
    }

    /**
     * Whether this log entry can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->attempt_count < $this->max_attempts;
    }

    /**
     * Increment the attempt counter.
     */
    public function incrementAttempt(): void
    {
        $this->increment('attempt_count');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRetryable(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_FAILED)
            ->whereColumn('attempt_count', '<', 'max_attempts');
    }
}
