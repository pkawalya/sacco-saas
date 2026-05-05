<?php

use App\Models\Tenant\EscalationChain;
use App\Models\Tenant\NotificationPreference;
use App\Models\Tenant\StaffAlert;
use App\Services\Tenant\StaffAlertService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->alertService = new StaffAlertService;
});

// ─── FR-AN-020: Staff alerts ────────────────────────────────

it('raises a staff alert', function () {
    $alert = $this->alertService->raiseAlert([
        'event_type' => 'threshold_exceeded',
        'title' => 'PAR Ratio exceeded 10%',
        'message' => 'PAR ratio has reached 12.5% — exceeds the 10% threshold.',
        'severity' => StaffAlert::SEVERITY_CRITICAL,
        'recipient_id' => 1,
        'recipient_name' => 'Manager Alice',
        'recipient_role' => 'manager',
        'source_module' => 'collections_engine',
    ]);

    expect($alert)->toBeInstanceOf(StaffAlert::class)
        ->and($alert->alert_id)->toStartWith('SA-')
        ->and($alert->status)->toBe(StaffAlert::STATUS_UNREAD)
        ->and($alert->severity)->toBe('critical');
});

it('retrieves unacknowledged alerts for a recipient', function () {
    $this->alertService->raiseAlert([
        'event_type' => 'loan_overdue',
        'title' => 'Alert 1',
        'message' => 'Test',
        'severity' => 'info',
        'recipient_id' => 1,
    ]);
    $this->alertService->raiseAlert([
        'event_type' => 'fraud_alert',
        'title' => 'Alert 2',
        'message' => 'Test',
        'severity' => 'warning',
        'recipient_id' => 1,
    ]);
    $this->alertService->raiseAlert([
        'event_type' => 'system_error',
        'title' => 'Alert 3',
        'message' => 'Test',
        'severity' => 'critical',
        'recipient_id' => 2,
    ]);

    $alerts = $this->alertService->getAlertsForRecipient(1);
    expect($alerts)->toHaveCount(2);
});

it('retrieves critical unacknowledged alerts', function () {
    $this->alertService->raiseAlert([
        'event_type' => 'threshold',
        'title' => 'Critical',
        'message' => 'Test',
        'severity' => StaffAlert::SEVERITY_CRITICAL,
        'recipient_id' => 1,
    ]);
    $this->alertService->raiseAlert([
        'event_type' => 'info_event',
        'title' => 'Info',
        'message' => 'Test',
        'severity' => StaffAlert::SEVERITY_INFO,
        'recipient_id' => 1,
    ]);

    $critical = $this->alertService->getCriticalAlerts();
    expect($critical)->toHaveCount(1)
        ->and($critical->first()->severity)->toBe('critical');
});

// ─── FR-AN-021: Acknowledgement ─────────────────────────────

it('marks an alert as read then acknowledged', function () {
    $alert = $this->alertService->raiseAlert([
        'event_type' => 'test',
        'title' => 'Test',
        'message' => 'Test',
        'severity' => 'info',
        'recipient_id' => 1,
    ]);

    $alert->markRead();
    expect($alert->fresh()->status)->toBe(StaffAlert::STATUS_READ)
        ->and($alert->fresh()->read_at)->not->toBeNull();

    $alert->acknowledge();
    expect($alert->fresh()->status)->toBe(StaffAlert::STATUS_ACKNOWLEDGED)
        ->and($alert->fresh()->acknowledged_at)->not->toBeNull();
});

// ─── FR-AN-022: Auto-escalation ─────────────────────────────

it('detects when an alert should be escalated', function () {
    $alert = StaffAlert::create([
        'alert_id' => 'SA-ESC-001',
        'event_type' => 'loan_overdue',
        'title' => 'Overdue Alert',
        'message' => 'Test',
        'severity' => 'warning',
        'recipient_id' => 1,
        'status' => StaffAlert::STATUS_UNREAD,
    ]);

    // Backdate via query to avoid timestamps overwrite
    StaffAlert::where('id', $alert->id)->update(['created_at' => now()->subMinutes(120)]);
    $alert->refresh();

    expect($alert->shouldEscalate(60))->toBeTrue()
        ->and($alert->shouldEscalate(180))->toBeFalse();
});

it('escalates an alert to next tier', function () {
    $alert = $this->alertService->raiseAlert([
        'event_type' => 'critical_error',
        'title' => 'System Down',
        'message' => 'Test',
        'severity' => 'critical',
        'recipient_id' => 1,
    ]);

    $alert->escalate(escalatedTo: 2, newTier: 2);

    expect($alert->fresh()->status)->toBe(StaffAlert::STATUS_ESCALATED)
        ->and($alert->fresh()->is_escalated)->toBeTrue()
        ->and($alert->fresh()->escalation_tier)->toBe(2)
        ->and($alert->fresh()->escalated_to)->toBe(2);
});

// ─── FR-AN-032: Escalation chains ───────────────────────────

it('configures an escalation chain', function () {
    $chain = $this->alertService->configureEscalationChain('loan_overdue', [
        ['role' => 'officer', 'minutes' => 30, 'channel' => 'sms'],
        ['role' => 'supervisor', 'minutes' => 60, 'channel' => 'email'],
        ['role' => 'manager', 'minutes' => 120, 'channel' => 'email'],
    ]);

    expect($chain)->toHaveCount(3);
    expect(EscalationChain::where('alert_type', 'loan_overdue')->count())->toBe(3);

    $tier2 = EscalationChain::where('alert_type', 'loan_overdue')->where('tier', 2)->first();
    expect($tier2->recipient_role)->toBe('supervisor')
        ->and($tier2->escalate_after_minutes)->toBe(60);
});

it('processes auto-escalation based on chain config', function () {
    // Set up chain: escalate after 0 minutes (for testing)
    $this->alertService->configureEscalationChain('test_event', [
        ['role' => 'officer', 'minutes' => 0],
        ['role' => 'supervisor', 'minutes' => 0],
    ]);

    // Create an alert at tier 1 that was created in the past
    StaffAlert::create([
        'alert_id' => 'SA-AUTO-001',
        'event_type' => 'test_event',
        'title' => 'Test Escalation',
        'message' => 'Should auto-escalate',
        'severity' => 'warning',
        'recipient_id' => 1,
        'status' => StaffAlert::STATUS_UNREAD,
        'escalation_tier' => 1,
        'created_at' => now()->subMinutes(5),
    ]);

    $stats = $this->alertService->processAutoEscalation();

    expect($stats['checked'])->toBe(1)
        ->and($stats['escalated'])->toBe(1);

    $escalated = StaffAlert::where('alert_id', 'SA-AUTO-001')->first();
    expect($escalated->status)->toBe(StaffAlert::STATUS_ESCALATED)
        ->and($escalated->escalation_tier)->toBe(2);
});

// ─── FR-AN-012: Member preferences ─────────────────────────

it('sets and retrieves member notification preferences', function () {
    $this->alertService->setMemberPreference(
        memberId: 1,
        eventType: NotificationPreference::EVENT_PAYMENT_RECEIVED,
        channel: NotificationPreference::CHANNEL_EMAIL,
        language: 'lg'
    );

    $channel = $this->alertService->getMemberPreferredChannel(1, NotificationPreference::EVENT_PAYMENT_RECEIVED);
    $language = $this->alertService->getMemberLanguage(1);

    expect($channel)->toBe('email')
        ->and($language)->toBe('lg');
});

it('returns default SMS channel when no preference set', function () {
    $channel = $this->alertService->getMemberPreferredChannel(999, 'some_event');
    expect($channel)->toBe('sms');
});

it('returns default English language when no preference set', function () {
    $language = $this->alertService->getMemberLanguage(999);
    expect($language)->toBe('en');
});

// ─── FR-AN-030: Management digest ──────────────────────────

it('generates a management digest', function () {
    $this->alertService->raiseAlert([
        'event_type' => 'par_threshold',
        'title' => 'PAR High',
        'message' => 'Test',
        'severity' => StaffAlert::SEVERITY_CRITICAL,
        'recipient_id' => 1,
        'source_module' => 'collections_engine',
    ]);
    $alert = $this->alertService->raiseAlert([
        'event_type' => 'daily_summary',
        'title' => 'Summary',
        'message' => 'Test',
        'severity' => StaffAlert::SEVERITY_INFO,
        'recipient_id' => 2,
        'source_module' => 'reporting',
    ]);
    $alert->acknowledge();

    $digest = $this->alertService->generateManagementDigest();

    expect($digest['total_alerts'])->toBe(2)
        ->and($digest['critical'])->toBe(1)
        ->and($digest['unacknowledged'])->toBe(1)
        ->and($digest['by_severity'])->toHaveCount(2)
        ->and($digest['by_module'])->toHaveCount(2);
});

// ─── FR-AN-045: Health report ───────────────────────────────

it('generates a notification health report', function () {
    $alert1 = $this->alertService->raiseAlert([
        'event_type' => 'test1',
        'title' => 'A',
        'message' => 'Test',
        'severity' => 'info',
        'recipient_id' => 1,
    ]);
    $alert1->acknowledge();

    $this->alertService->raiseAlert([
        'event_type' => 'test2',
        'title' => 'B',
        'message' => 'Test',
        'severity' => 'critical',
        'recipient_id' => 2,
    ]);

    $report = $this->alertService->getHealthReport();

    expect($report['total_alerts_24h'])->toBe(2)
        ->and($report['acknowledgement_rate'])->toBe(50.0)
        ->and($report['critical_pending'])->toBe(1);
});
