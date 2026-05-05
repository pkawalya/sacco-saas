<?php

namespace App\Services\Tenant;

use App\Models\Tenant\EscalationChain;
use App\Models\Tenant\NotificationPreference;
use App\Models\Tenant\StaffAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Staff Alert & Notification Engine (Full).
 *
 * FR-AN-010–012: Member notifications, masking, preferences
 * FR-AN-020–022: Staff alerts, acknowledgement, auto-escalation
 * FR-AN-030–032: Management digest, escalation chains
 * FR-AN-040–045: Health reports
 */
class StaffAlertService
{
    // ─── Staff Alerts (FR-AN-020) ──────────────────────────────

    /**
     * Raise a staff alert.
     *
     * @param  array<string, mixed>  $data
     */
    public function raiseAlert(array $data): StaffAlert
    {
        $alertId = 'SA-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        return StaffAlert::create(array_merge($data, [
            'alert_id' => $alertId,
            'status' => StaffAlert::STATUS_UNREAD,
        ]));
    }

    /**
     * Get unacknowledged alerts for a recipient (FR-AN-020).
     *
     * @return Collection<int, StaffAlert>
     */
    public function getAlertsForRecipient(int $recipientId): Collection
    {
        return StaffAlert::query()
            ->forRecipient($recipientId)
            ->unacknowledged()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get critical unacknowledged alerts.
     *
     * @return Collection<int, StaffAlert>
     */
    public function getCriticalAlerts(): Collection
    {
        return StaffAlert::query()
            ->critical()
            ->unacknowledged()
            ->orderByDesc('created_at')
            ->get();
    }

    // ─── Auto-Escalation (FR-AN-022, FR-AN-032) ───────────────

    /**
     * Process auto-escalation for all unacknowledged alerts.
     *
     * @return array{escalated: int, checked: int}
     */
    public function processAutoEscalation(): array
    {
        $stats = ['escalated' => 0, 'checked' => 0];

        $alerts = StaffAlert::query()
            ->unacknowledged()
            ->where('is_escalated', false)
            ->get();

        foreach ($alerts as $alert) {
            $stats['checked']++;

            // Find the applicable chain
            $chain = EscalationChain::query()
                ->where('alert_type', $alert->event_type)
                ->where('tier', $alert->escalation_tier + 1)
                ->where('is_active', true)
                ->first();

            if (! $chain) {
                continue;
            }

            if ($alert->shouldEscalate($chain->escalate_after_minutes)) {
                $alert->escalate(
                    escalatedTo: 0, // Would be resolved from role in production
                    newTier: $chain->tier,
                );
                $stats['escalated']++;
            }
        }

        return $stats;
    }

    // ─── Member Preferences (FR-AN-012) ────────────────────────

    /**
     * Get member's preferred channel for an event type.
     */
    public function getMemberPreferredChannel(int $memberId, string $eventType): string
    {
        $pref = NotificationPreference::query()
            ->where('member_id', $memberId)
            ->where('event_type', $eventType)
            ->where('is_enabled', true)
            ->first();

        return $pref ? $pref->channel : NotificationPreference::CHANNEL_SMS;
    }

    /**
     * Get member's preferred language.
     */
    public function getMemberLanguage(int $memberId): string
    {
        $pref = NotificationPreference::query()
            ->where('member_id', $memberId)
            ->where('is_enabled', true)
            ->first();

        return $pref ? $pref->language : 'en';
    }

    /**
     * Set member notification preference.
     */
    public function setMemberPreference(int $memberId, string $eventType, string $channel, bool $enabled = true, string $language = 'en'): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            [
                'member_id' => $memberId,
                'event_type' => $eventType,
                'channel' => $channel,
            ],
            [
                'is_enabled' => $enabled,
                'language' => $language,
            ]
        );
    }

    // ─── Management Digest (FR-AN-030) ─────────────────────────

    /**
     * Generate a management digest summary.
     *
     * @return array{total_alerts: int, critical: int, unacknowledged: int, escalated: int, by_severity: Collection, by_module: Collection}
     */
    public function generateManagementDigest(): array
    {
        $today = now()->startOfDay();
        $alerts = StaffAlert::query()
            ->where('created_at', '>=', $today)
            ->get();

        return [
            'total_alerts' => $alerts->count(),
            'critical' => $alerts->where('severity', StaffAlert::SEVERITY_CRITICAL)->count(),
            'unacknowledged' => $alerts->whereNotIn('status', [StaffAlert::STATUS_ACKNOWLEDGED])->count(),
            'escalated' => $alerts->where('is_escalated', true)->count(),
            'by_severity' => $alerts->groupBy('severity')->map->count(),
            'by_module' => $alerts->groupBy('source_module')->map->count(),
        ];
    }

    // ─── Health Report (FR-AN-045) ─────────────────────────────

    /**
     * Generate notification health report.
     *
     * @return array{total_alerts_24h: int, acknowledgement_rate: float, avg_response_minutes: float, escalation_rate: float, critical_pending: int}
     */
    public function getHealthReport(): array
    {
        $since = now()->subDay();
        $alerts = StaffAlert::query()
            ->where('created_at', '>=', $since)
            ->get();

        $total = $alerts->count();
        $acknowledged = $alerts->where('status', StaffAlert::STATUS_ACKNOWLEDGED)->count();
        $escalated = $alerts->where('is_escalated', true)->count();

        $avgResponseMinutes = $alerts
            ->whereNotNull('acknowledged_at')
            ->avg(fn (StaffAlert $a): float => $a->created_at->diffInMinutes($a->acknowledged_at));

        return [
            'total_alerts_24h' => $total,
            'acknowledgement_rate' => $total > 0 ? round(($acknowledged / $total) * 100, 2) : 0.0,
            'avg_response_minutes' => round($avgResponseMinutes ?? 0, 1),
            'escalation_rate' => $total > 0 ? round(($escalated / $total) * 100, 2) : 0.0,
            'critical_pending' => $alerts
                ->where('severity', StaffAlert::SEVERITY_CRITICAL)
                ->whereNotIn('status', [StaffAlert::STATUS_ACKNOWLEDGED])
                ->count(),
        ];
    }

    // ─── Escalation Chain Management (FR-AN-032) ───────────────

    /**
     * Set up an escalation chain.
     *
     * @param  array<int, array{role: string, minutes: int, channel?: string}>  $tiers
     * @return Collection<int, EscalationChain>
     */
    public function configureEscalationChain(string $alertType, array $tiers): Collection
    {
        $created = collect();

        foreach ($tiers as $tierNum => $config) {
            $chain = EscalationChain::updateOrCreate(
                ['alert_type' => $alertType, 'tier' => $tierNum + 1],
                [
                    'recipient_role' => $config['role'],
                    'escalate_after_minutes' => $config['minutes'],
                    'notification_channel' => $config['channel'] ?? 'email',
                    'is_active' => true,
                ]
            );
            $created->push($chain);
        }

        return $created;
    }
}
