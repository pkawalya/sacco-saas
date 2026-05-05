<?php

use App\Models\Tenant\NotificationLog;
use App\Models\Tenant\NotificationTemplate;
use App\Services\Tenant\NotificationService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->notifService = new NotificationService;
});

// ─── Helpers ────────────────────────────────────────────────────

function createSmsTemplate(
    string $code = 'TEST_TEMPLATE',
    string $eventType = 'test.event',
    string $body = 'Hello {member_name}, your ref is {reference}.',
    bool $maskSensitiveData = false,
    string $priority = 'normal',
): NotificationTemplate {
    return NotificationTemplate::create([
        'template_code' => $code,
        'name' => "Test Template: {$code}",
        'event_type' => $eventType,
        'module' => 'member_management',
        'channel' => NotificationTemplate::CHANNEL_SMS,
        'subject' => null,
        'body' => $body,
        'merge_fields' => [
            ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
            ['key' => 'reference', 'label' => 'Reference', 'sample' => 'REF-001'],
        ],
        'priority' => $priority,
        'is_mandatory' => true,
        'is_active' => true,
        'mask_sensitive_data' => $maskSensitiveData,
    ]);
}

function createEmailTemplate(string $code = 'TEST_EMAIL', string $eventType = 'test.email'): NotificationTemplate
{
    return NotificationTemplate::create([
        'template_code' => $code,
        'name' => "Email Template: {$code}",
        'event_type' => $eventType,
        'module' => 'loan_management',
        'channel' => NotificationTemplate::CHANNEL_EMAIL,
        'subject' => 'Notification for {member_name}',
        'body' => "Dear {member_name},\n\nYour account {account_number} has activity.\n\nRef: {reference}.",
        'merge_fields' => [
            ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John'],
            ['key' => 'account_number', 'label' => 'Account', 'sample' => 'SAV-0001'],
            ['key' => 'reference', 'label' => 'Ref', 'sample' => 'REF-001'],
        ],
        'priority' => 'normal',
        'is_mandatory' => false,
        'is_active' => true,
        'mask_sensitive_data' => true,
    ]);
}

// ─── FR-AN-040: Template dispatch with merge fields ───────────

it('dispatches a notification using a template and renders merge fields', function () {
    createSmsTemplate();

    $log = $this->notifService->dispatch(
        eventType: 'test.event',
        recipientIdentifier: '+256700123456',
        mergeData: ['member_name' => 'Alice Wamala', 'reference' => 'TXN-2026-001'],
        recipientId: 42,
    );

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->rendered_body)->toBe('Hello Alice Wamala, your ref is TXN-2026-001.')
        ->and($log->channel)->toBe('sms')
        ->and($log->event_type)->toBe('test.event')
        ->and($log->recipient_identifier)->toBe('+256700123456')
        ->and($log->recipient_id)->toBe(42)
        ->and($log->status)->toBe(NotificationLog::STATUS_SENT);
});

it('renders email subject merge fields correctly', function () {
    createEmailTemplate();

    $log = $this->notifService->dispatch(
        eventType: 'test.email',
        recipientIdentifier: 'alice@example.com',
        mergeData: [
            'member_name' => 'Alice',
            'account_number' => 'SAV-0001-2026',
            'reference' => 'REF-100',
        ],
    );

    expect($log->subject)->toBe('Notification for Alice')
        ->and($log->channel)->toBe('email');
});

it('returns null when no active template is found for the event', function () {
    $log = $this->notifService->dispatch(
        eventType: 'nonexistent.event',
        recipientIdentifier: '+256700000000',
    );

    expect($log)->toBeNull();
});

it('skips inactive templates', function () {
    $template = createSmsTemplate();
    $template->update(['is_active' => false]);

    $log = $this->notifService->dispatch(
        eventType: 'test.event',
        recipientIdentifier: '+256700000000',
    );

    expect($log)->toBeNull();
});

// ─── FR-AN-010: Data masking ──────────────────────────────────

it('masks account number in notification body when enabled', function () {
    createEmailTemplate('MASK_TEST', 'mask.test');

    $log = $this->notifService->dispatch(
        eventType: 'mask.test',
        recipientIdentifier: 'test@example.com',
        mergeData: [
            'member_name' => 'Bob',
            'account_number' => 'SAV-0001-2026',
            'reference' => 'REF-001',
        ],
    );

    // account_number should be masked (first 4 + last 4 visible)
    expect($log->rendered_body)->not->toContain('SAV-0001-2026')
        ->and($log->rendered_body)->toContain('SAV-')
        ->and($log->rendered_body)->toContain('2026');
});

it('does not mask data when mask_sensitive_data is false', function () {
    createSmsTemplate('NOMASK', 'nomask.event', 'Account: {account_number}', false);

    $log = $this->notifService->dispatch(
        eventType: 'nomask.event',
        recipientIdentifier: '+256700000000',
        mergeData: ['account_number' => 'SAV-0001-2026', 'member_name' => 'X', 'reference' => 'R'],
    );

    expect($log->rendered_body)->toContain('SAV-0001-2026');
});

it('masks phone numbers correctly', function () {
    $masked = $this->notifService->maskSensitiveData([
        'phone' => '+256700123456',
        'member_name' => 'Test',
    ]);

    // Phone: keep first 3 + last 3 → +25*******456
    expect($masked['phone'])->not->toBe('+256700123456')
        ->and(str_starts_with($masked['phone'], '+25'))->toBeTrue()
        ->and(str_ends_with($masked['phone'], '456'))->toBeTrue()
        ->and($masked['member_name'])->toBe('Test');  // Non-sensitive unchanged
});

it('masks national ID correctly', function () {
    $masked = $this->notifService->maskSensitiveData([
        'national_id' => 'CM-12345-ABCDE',
    ]);

    // National ID: keep first 2 + last 3
    expect($masked['national_id'])->not->toBe('CM-12345-ABCDE')
        ->and(str_starts_with($masked['national_id'], 'CM'))->toBeTrue()
        ->and(str_ends_with($masked['national_id'], 'CDE'))->toBeTrue();
});

// ─── FR-AN-041: Immutable audit log ──────────────────────────

it('creates an immutable audit log entry for every dispatch', function () {
    createSmsTemplate();

    $this->notifService->dispatch(
        eventType: 'test.event',
        recipientIdentifier: '+256700123456',
        mergeData: ['member_name' => 'Charlie', 'reference' => 'REF-X'],
        sourceInfo: ['module' => 'member_management', 'reference' => 'MEM-001', 'id' => 7],
    );

    expect(NotificationLog::count())->toBe(1);

    $log = NotificationLog::first();
    expect($log->source_module)->toBe('member_management')
        ->and($log->source_reference)->toBe('MEM-001')
        ->and($log->source_id)->toBe(7)
        ->and($log->notification_template_id)->not->toBeNull();
});

it('prevents invalid status transitions on notification log', function () {
    createSmsTemplate();

    $log = $this->notifService->dispatch(
        eventType: 'test.event',
        recipientIdentifier: '+256700000000',
        mergeData: ['member_name' => 'Test', 'reference' => 'R'],
    );

    // Log is now 'sent'. Transitioning to 'pending' should fail.
    expect(fn () => $log->transitionTo(NotificationLog::STATUS_PENDING))
        ->toThrow(RuntimeException::class, 'Cannot transition');
});

it('allows valid forward status transitions', function () {
    createSmsTemplate();

    $log = $this->notifService->dispatch(
        eventType: 'test.event',
        recipientIdentifier: '+256700000000',
        mergeData: ['member_name' => 'Test', 'reference' => 'R'],
    );

    // sent → delivered is valid
    $log->transitionTo(NotificationLog::STATUS_DELIVERED);

    expect($log->fresh()->status)->toBe(NotificationLog::STATUS_DELIVERED)
        ->and($log->fresh()->delivered_at)->not->toBeNull();
});

it('records timestamps for each status transition', function () {
    $log = NotificationLog::create([
        'recipient_type' => 'member',
        'recipient_identifier' => '+256700000000',
        'channel' => 'sms',
        'event_type' => 'test.timestamp',
        'priority' => 'normal',
        'rendered_body' => 'Test body',
        'status' => NotificationLog::STATUS_PENDING,
        'attempt_count' => 0,
        'max_attempts' => 3,
    ]);

    // pending → queued
    $log->transitionTo(NotificationLog::STATUS_QUEUED);
    expect($log->fresh()->queued_at)->not->toBeNull();

    // queued → sent
    $log->transitionTo(NotificationLog::STATUS_SENT);
    expect($log->fresh()->sent_at)->not->toBeNull();

    // sent → delivered
    $log->transitionTo(NotificationLog::STATUS_DELIVERED);
    expect($log->fresh()->delivered_at)->not->toBeNull();
});

// ─── FR-AN-001: Channel failover ─────────────────────────────

it('sets max_attempts based on priority level', function () {
    createSmsTemplate('CRITICAL_TPL', 'critical.event', 'Critical: {member_name}', false, 'critical');

    $log = $this->notifService->dispatch(
        eventType: 'critical.event',
        recipientIdentifier: '+256700000000',
        mergeData: ['member_name' => 'Test', 'reference' => 'R'],
    );

    expect($log->max_attempts)->toBe(5);  // Critical gets 5 attempts
});

it('correctly identifies retryable notification logs', function () {
    // Create a failed log with remaining attempts
    $log = NotificationLog::create([
        'recipient_type' => 'member',
        'recipient_identifier' => '+256700000000',
        'channel' => 'sms',
        'event_type' => 'test.retry',
        'priority' => 'normal',
        'rendered_body' => 'Test',
        'status' => NotificationLog::STATUS_FAILED,
        'attempt_count' => 1,
        'max_attempts' => 3,
    ]);

    expect($log->canRetry())->toBeTrue();

    // Exhaust attempts
    $log->update(['attempt_count' => 3]);
    expect($log->fresh()->canRetry())->toBeFalse();
});

it('tracks failover channel separately from primary channel', function () {
    $log = NotificationLog::create([
        'recipient_type' => 'member',
        'recipient_identifier' => '+256700000000',
        'channel' => 'sms',
        'event_type' => 'test.failover',
        'priority' => 'normal',
        'rendered_body' => 'Test',
        'status' => NotificationLog::STATUS_PENDING,
        'attempt_count' => 0,
        'max_attempts' => 3,
        'failover_channel' => 'email',
    ]);

    expect($log->channel)->toBe('sms')
        ->and($log->failover_channel)->toBe('email');
});

// ─── Template features ───────────────────────────────────────

it('returns correct failover order for each channel', function () {
    $smsTemplate = createSmsTemplate();
    $emailTemplate = createEmailTemplate();

    expect($smsTemplate->getFailoverChannels())->toBe(['email', 'push', 'in_app'])
        ->and($emailTemplate->getFailoverChannels())->toBe(['sms', 'push', 'in_app']);
});

it('supports direct dispatch without a template', function () {
    $log = $this->notifService->dispatchDirect(
        channel: 'sms',
        eventType: 'system.direct',
        recipientIdentifier: '+256700999999',
        body: 'System maintenance scheduled for tonight.',
        priority: 'high',
        sourceInfo: ['module' => 'notifications_engine'],
    );

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->notification_template_id)->toBeNull()
        ->and($log->rendered_body)->toBe('System maintenance scheduled for tonight.')
        ->and($log->priority)->toBe('high')
        ->and($log->status)->toBe(NotificationLog::STATUS_SENT);
});

// ─── Statistics ──────────────────────────────────────────────

it('computes notification statistics correctly', function () {
    createSmsTemplate('STAT_TPL', 'stat.event', 'Stats {member_name}');

    // Dispatch 3 notifications
    for ($i = 1; $i <= 3; $i++) {
        $this->notifService->dispatch(
            eventType: 'stat.event',
            recipientIdentifier: "+25670000000{$i}",
            mergeData: ['member_name' => "User {$i}", 'reference' => "R-{$i}"],
        );
    }

    $stats = $this->notifService->getStats();

    expect($stats['total'])->toBe(3)
        ->and($stats['sent'])->toBe(3)
        ->and($stats['delivery_rate'])->toBe(100.0);
});
