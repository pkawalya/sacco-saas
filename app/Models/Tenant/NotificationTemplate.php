<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Notification template with merge-field support.
 *
 * FR-AN-040: Template manager with merge fields.
 * FR-AN-010: Data masking flag for sensitive content.
 *
 * @property int $id
 * @property string $template_code
 * @property string $name
 * @property string $event_type
 * @property string|null $module
 * @property string $channel
 * @property string|null $subject
 * @property string $body
 * @property array|null $merge_fields
 * @property string $priority
 * @property bool $is_mandatory
 * @property bool $is_active
 * @property bool $mask_sensitive_data
 */
class NotificationTemplate extends Model
{
    // ─── Channel Constants ──────────────────────────────────────
    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PUSH = 'push';

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNELS = [
        self::CHANNEL_SMS => 'SMS',
        self::CHANNEL_EMAIL => 'Email',
        self::CHANNEL_PUSH => 'Push Notification',
        self::CHANNEL_IN_APP => 'In-App',
    ];

    // ─── Priority Constants ─────────────────────────────────────
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'High',
        self::PRIORITY_CRITICAL => 'Critical',
    ];

    // ─── Channel Failover Order (FR-AN-001) ─────────────────────
    public const FAILOVER_ORDER = [
        self::CHANNEL_SMS => [self::CHANNEL_EMAIL, self::CHANNEL_PUSH, self::CHANNEL_IN_APP],
        self::CHANNEL_EMAIL => [self::CHANNEL_SMS, self::CHANNEL_PUSH, self::CHANNEL_IN_APP],
        self::CHANNEL_PUSH => [self::CHANNEL_IN_APP, self::CHANNEL_SMS, self::CHANNEL_EMAIL],
        self::CHANNEL_IN_APP => [self::CHANNEL_PUSH, self::CHANNEL_EMAIL, self::CHANNEL_SMS],
    ];

    protected $fillable = [
        'template_code',
        'name',
        'event_type',
        'module',
        'channel',
        'subject',
        'body',
        'merge_fields',
        'priority',
        'is_mandatory',
        'is_active',
        'mask_sensitive_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'merge_fields' => 'array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'mask_sensitive_data' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

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
    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    // ─── Merge Field Rendering ──────────────────────────────────

    /**
     * Render the template body by replacing merge fields with actual values.
     *
     * @param  array<string, string>  $data  Key-value pairs of merge field data
     */
    public function renderBody(array $data): string
    {
        return $this->renderContent($this->body, $data);
    }

    /**
     * Render the template subject.
     *
     * @param  array<string, string>  $data
     */
    public function renderSubject(array $data): string
    {
        return $this->renderContent($this->subject ?? '', $data);
    }

    /**
     * Replace merge field placeholders in content.
     *
     * @param  array<string, string>  $data
     */
    private function renderContent(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{'.$key.'}', (string) $value, $content);
        }

        return $content;
    }

    /**
     * Get the failover channels for this template's primary channel.
     *
     * @return array<int, string>
     */
    public function getFailoverChannels(): array
    {
        return self::FAILOVER_ORDER[$this->channel] ?? [];
    }
}
