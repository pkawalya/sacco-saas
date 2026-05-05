<?php

namespace App\Services\Tenant;

use App\Mail\TemplateMail;
use App\Models\Tenant\NotificationLog;
use App\Models\Tenant\NotificationTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Core notification dispatch and management service.
 *
 * Covers:
 * - FR-AN-001: Channel failover with retry logic
 * - FR-AN-010: Data masking for sensitive content
 * - FR-AN-040: Template-based dispatch with merge fields
 * - FR-AN-041: Immutable audit logging
 */
class NotificationService
{
    // ─── Sensitive Data Patterns (FR-AN-010) ────────────────────

    /**
     * Patterns for masking sensitive data in notification content.
     *
     * @var array<string, string>
     */
    private const MASK_PATTERNS = [
        'account_number' => '/(\d{4})\d+(\d{4})/',        // Keep first 4 and last 4
        'phone' => '/(\+?\d{3})\d+(\d{3})/',              // Keep country code + last 3
        'national_id' => '/^(.{2}).+(.{3})$/',             // Keep first 2 + last 3
        'amount' => '/^(.+)$/',                             // No masking for amounts
    ];

    /**
     * Dispatch a notification using a template.
     *
     * FR-AN-040: Resolves template by event_type, renders merge fields,
     * and creates an audit log entry.
     *
     * @param  array<string, string>  $mergeData  Key-value pairs for merge fields
     * @param  array<string, mixed>  $sourceInfo  Optional source tracking: [module, reference, id]
     */
    public function dispatch(
        string $eventType,
        string $recipientIdentifier,
        array $mergeData = [],
        string $recipientType = NotificationLog::RECIPIENT_MEMBER,
        ?int $recipientId = null,
        ?string $preferredChannel = null,
        array $sourceInfo = [],
    ): ?NotificationLog {
        // Find active template for this event
        $template = NotificationTemplate::query()
            ->active()
            ->forEvent($eventType)
            ->first();

        if (! $template) {
            Log::warning("No active notification template found for event: {$eventType}");

            return null;
        }

        $channel = $preferredChannel ?? $template->channel;

        // Apply data masking if enabled (FR-AN-010)
        $processedData = $template->mask_sensitive_data
            ? $this->maskSensitiveData($mergeData)
            : $mergeData;

        // Render content
        $renderedBody = $template->renderBody($processedData);
        $renderedSubject = $template->renderSubject($processedData);

        // Create audit log entry (FR-AN-041)
        $log = NotificationLog::create([
            'notification_template_id' => $template->id,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_identifier' => $recipientIdentifier,
            'channel' => $channel,
            'event_type' => $eventType,
            'priority' => $template->priority,
            'subject' => $renderedSubject ?: null,
            'rendered_body' => $renderedBody,
            'status' => NotificationLog::STATUS_PENDING,
            'attempt_count' => 0,
            'max_attempts' => $this->getMaxAttempts($template->priority),
            'source_module' => $sourceInfo['module'] ?? null,
            'source_reference' => $sourceInfo['reference'] ?? null,
            'source_id' => $sourceInfo['id'] ?? null,
        ]);

        // Attempt to send
        $sent = $this->attemptSend($log, $channel);

        // FR-AN-001: Channel failover if primary fails
        if (! $sent) {
            $sent = $this->attemptFailover($log, $template);
        }

        return $log->fresh();
    }

    /**
     * Dispatch a notification directly without a template (for system-generated messages).
     *
     * @param  array<string, mixed>  $sourceInfo
     */
    public function dispatchDirect(
        string $channel,
        string $eventType,
        string $recipientIdentifier,
        string $body,
        ?string $subject = null,
        string $recipientType = NotificationLog::RECIPIENT_MEMBER,
        ?int $recipientId = null,
        string $priority = NotificationTemplate::PRIORITY_NORMAL,
        array $sourceInfo = [],
    ): NotificationLog {
        $log = NotificationLog::create([
            'notification_template_id' => null,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_identifier' => $recipientIdentifier,
            'channel' => $channel,
            'event_type' => $eventType,
            'priority' => $priority,
            'subject' => $subject,
            'rendered_body' => $body,
            'status' => NotificationLog::STATUS_PENDING,
            'attempt_count' => 0,
            'max_attempts' => $this->getMaxAttempts($priority),
            'source_module' => $sourceInfo['module'] ?? null,
            'source_reference' => $sourceInfo['reference'] ?? null,
            'source_id' => $sourceInfo['id'] ?? null,
        ]);

        $this->attemptSend($log, $channel);

        return $log->fresh();
    }

    /**
     * Retry failed notifications that haven't exceeded max attempts.
     *
     * @return int Number of notifications retried
     */
    public function retryFailed(): int
    {
        $retryable = NotificationLog::query()
            ->retryable()
            ->get();

        $retried = 0;

        foreach ($retryable as $log) {
            $log->incrementAttempt();
            $sent = $this->attemptSend($log, $log->failover_channel ?? $log->channel);

            if ($sent) {
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * FR-AN-010: Mask sensitive data in merge field values.
     *
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    public function maskSensitiveData(array $data): array
    {
        $masked = [];
        $sensitiveKeys = ['account_number', 'phone', 'national_id', 'member_phone', 'member_national_id'];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys, true) && is_string($value) && $value !== '') {
                $masked[$key] = $this->applyMask($key, $value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Get notification statistics for a time period.
     *
     * @return array{total: int, sent: int, delivered: int, failed: int, pending: int, delivery_rate: float}
     */
    public function getStats(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = NotificationLog::query();

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $total = $query->count();
        $sent = (clone $query)->where('status', NotificationLog::STATUS_SENT)->count();
        $delivered = (clone $query)->where('status', NotificationLog::STATUS_DELIVERED)->count();
        $failed = (clone $query)->where('status', NotificationLog::STATUS_FAILED)->count();
        $pending = (clone $query)->where('status', NotificationLog::STATUS_PENDING)->count();

        $successCount = $sent + $delivered;
        $deliveryRate = $total > 0 ? round(($successCount / $total) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'delivery_rate' => $deliveryRate,
        ];
    }

    /**
     * Get all templates grouped by event type.
     *
     * @return Collection<string, Collection<int, NotificationTemplate>>
     */
    public function getTemplatesByEvent(): Collection
    {
        return NotificationTemplate::query()
            ->active()
            ->orderBy('event_type')
            ->orderBy('channel')
            ->get()
            ->groupBy('event_type');
    }

    // ─── Private Helpers ────────────────────────────────────────

    /**
     * Attempt to send a notification via the given channel.
     *
     * Email is dispatched via Laravel Mail (Gmail SMTP).
     * SMS, Push, and In-App channels are logged and marked sent for future gateway integration.
     */
    private function attemptSend(NotificationLog $log, string $channel): bool
    {
        $log->incrementAttempt();

        try {
            $success = $this->dispatchToChannel($channel, $log);

            if ($success) {
                $log->transitionTo(NotificationLog::STATUS_SENT);
                $log->update([
                    'provider' => $this->getProviderName($channel),
                    'external_id' => ($channel === NotificationTemplate::CHANNEL_EMAIL ? 'smtp_' : 'sim_').uniqid(),
                ]);

                return true;
            }

            $log->transitionTo(NotificationLog::STATUS_FAILED);
            $log->update(['error_message' => 'Channel send failed']);

            return false;
        } catch (\Throwable $e) {
            Log::error("Notification send failed: {$e->getMessage()}", [
                'log_id' => $log->id,
                'channel' => $channel,
            ]);

            if ($log->canTransitionTo(NotificationLog::STATUS_FAILED)) {
                $log->transitionTo(NotificationLog::STATUS_FAILED);
                $log->update(['error_message' => $e->getMessage()]);
            }

            return false;
        }
    }

    /**
     * FR-AN-001: Attempt failover channels when primary channel fails.
     */
    private function attemptFailover(NotificationLog $log, NotificationTemplate $template): bool
    {
        $failoverChannels = $template->getFailoverChannels();

        foreach ($failoverChannels as $fallbackChannel) {
            // Reset status to allow retry via failover
            if ($log->status === NotificationLog::STATUS_FAILED) {
                $log->update([
                    'failover_channel' => $fallbackChannel,
                    'status' => NotificationLog::STATUS_FAILED,
                ]);
            }

            $log->refresh();

            $sent = $this->attemptSend($log, $fallbackChannel);

            if ($sent) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch to the appropriate channel driver.
     *
     * Email → Gmail SMTP via TemplateMail mailable (queued).
     * Other channels → logged; returns true to mark sent (gateway integration pending).
     */
    private function dispatchToChannel(string $channel, NotificationLog $log): bool
    {
        if ($channel === NotificationTemplate::CHANNEL_EMAIL) {
            $recipient = $log->recipient_identifier;

            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                Log::warning('NotificationService: invalid email address', ['recipient' => $recipient]);

                return false;
            }

            $template = $log->template;

            if (! $template) {
                // Direct dispatch without template — send raw body
                Mail::html($log->rendered_body, function ($message) use ($recipient, $log) {
                    $message->to($recipient)->subject($log->subject ?? config('app.name').' Notification');
                });

                return true;
            }

            Mail::to($recipient)->queue(new TemplateMail($template, []));

            return true;
        }

        // SMS / Push / In-App — placeholder; real gateway integration per module sprint
        Log::info("NotificationService: [{$channel}] channel queued for future gateway.", [
            'event_type' => $log->event_type,
            'recipient' => $log->recipient_identifier,
        ]);

        return true;
    }

    /**
     * Get the provider name for a channel.
     */
    private function getProviderName(string $channel): string
    {
        return match ($channel) {
            NotificationTemplate::CHANNEL_SMS => 'sms_gateway',
            NotificationTemplate::CHANNEL_EMAIL => 'smtp',
            NotificationTemplate::CHANNEL_PUSH => 'fcm',
            NotificationTemplate::CHANNEL_IN_APP => 'internal',
            default => 'unknown',
        };
    }

    /**
     * Apply masking to a sensitive value.
     */
    private function applyMask(string $key, string $value): string
    {
        // Normalise key: member_phone → phone, member_national_id → national_id
        $normalisedKey = str_replace('member_', '', $key);

        return match ($normalisedKey) {
            'account_number' => $this->maskMiddle($value, 4, 4),
            'phone' => $this->maskMiddle($value, 3, 3),
            'national_id' => $this->maskMiddle($value, 2, 3),
            default => $value,
        };
    }

    /**
     * Mask characters in the middle of a string, keeping the prefix and suffix visible.
     */
    private function maskMiddle(string $value, int $prefixLen, int $suffixLen): string
    {
        $len = mb_strlen($value);

        if ($len <= ($prefixLen + $suffixLen)) {
            return str_repeat('*', $len);
        }

        $prefix = mb_substr($value, 0, $prefixLen);
        $suffix = mb_substr($value, -$suffixLen);
        $maskLen = $len - $prefixLen - $suffixLen;

        return $prefix.str_repeat('*', $maskLen).$suffix;
    }

    /**
     * Get max retry attempts based on priority.
     */
    private function getMaxAttempts(string $priority): int
    {
        return match ($priority) {
            NotificationTemplate::PRIORITY_CRITICAL => 5,
            NotificationTemplate::PRIORITY_HIGH => 4,
            NotificationTemplate::PRIORITY_NORMAL => 3,
            NotificationTemplate::PRIORITY_LOW => 2,
            default => 3,
        };
    }
}
