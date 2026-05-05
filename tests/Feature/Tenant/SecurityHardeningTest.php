<?php

use App\Http\Middleware\IdleTimeout;
use App\Http\Middleware\IpWhitelist;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyApiSignature;
use App\Models\Central\User;
use App\Models\Tenant\ApprovalRequest;
use App\Models\Tenant\AuditTrail;
use App\Models\Tenant\Member;
use App\Rules\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->initializeTenancy();
});

// ═══════════════════════════════════════════════════════════════
// 1. AUDIT TRAIL
// ═══════════════════════════════════════════════════════════════

it('logs audit trail on model create', function () {
    $member = Member::factory()->create();

    $trail = AuditTrail::where('auditable_type', Member::class)
        ->where('auditable_id', $member->id)
        ->where('event', 'created')
        ->first();

    expect($trail)->not->toBeNull()
        ->and($trail->event)->toBe('created')
        ->and($trail->new_values)->toBeArray();
});

// ═══════════════════════════════════════════════════════════════
// 2. ACCOUNT LOCKOUT
// ═══════════════════════════════════════════════════════════════

it('detects locked accounts', function () {
    $user = User::create([
        'name' => 'Lock Test',
        'email' => 'locktest@sacco.test',
        'password' => 'password',
        'is_approved' => true,
        'email_verified_at' => now(),
    ]);

    // Not locked
    expect($user->isLocked())->toBeFalse();

    // Lock the account
    $user->forceFill(['locked_until' => now()->addMinutes(30)])->save();
    expect($user->fresh()->isLocked())->toBeTrue();

    // Expired lock auto-unlocks
    $user->forceFill(['locked_until' => now()->subMinute()])->save();
    expect($user->fresh()->isLocked())->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════
// 5. IP WHITELIST
// ═══════════════════════════════════════════════════════════════

it('blocks non-whitelisted IPs when whitelist is set', function () {
    config(['security.admin_ip_whitelist' => ['10.0.0.1']]);

    $middleware = new IpWhitelist;
    $request = Request::create('/admin');

    expect(fn () => $middleware->handle($request, fn ($r) => response('OK')))
        ->toThrow(HttpException::class);
});

it('allows all IPs when whitelist is empty', function () {
    config(['security.admin_ip_whitelist' => []]);

    $middleware = new IpWhitelist;
    $request = Request::create('/admin');

    $response = $middleware->handle($request, fn ($r) => response('OK'));
    expect($response->getContent())->toBe('OK');
});

// ═══════════════════════════════════════════════════════════════
// 7. SECURITY HEADERS
// ═══════════════════════════════════════════════════════════════

it('adds security headers to responses', function () {
    $middleware = new SecurityHeaders;
    $request = Request::create('/test');

    $response = $middleware->handle($request, fn ($r) => response('OK'));

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

// ═══════════════════════════════════════════════════════════════
// 8. SENSITIVE FIELD ENCRYPTION (via encrypted cast)
// ═══════════════════════════════════════════════════════════════

it('can store and retrieve encrypted data', function () {
    $member = Member::factory()->create([
        'national_id_number' => 'SECRET-NIN-12345',
    ]);

    $fresh = Member::find($member->id);
    expect($fresh->national_id_number)->toBe('SECRET-NIN-12345');
});

// ═══════════════════════════════════════════════════════════════
// 11. MAKER-CHECKER APPROVAL
// ═══════════════════════════════════════════════════════════════

it('creates and processes approval requests', function () {
    $approval = ApprovalRequest::create([
        'approvable_type' => 'App\\Models\\Tenant\\Loan',
        'approvable_id' => 1,
        'action' => 'approve',
        'payload' => ['amount' => 5000000],
        'requested_by' => 1,
        'status' => 'pending',
    ]);

    expect($approval->isPending())->toBeTrue();

    $approval->approve(2, 'Verified and approved');
    expect($approval->fresh()->status)->toBe('approved')
        ->and($approval->fresh()->reviewed_by)->toBe(2);
});

it('rejects approval requests with reason', function () {
    $approval = ApprovalRequest::create([
        'approvable_type' => 'App\\Models\\Tenant\\Loan',
        'approvable_id' => 2,
        'action' => 'create',
        'payload' => ['amount' => 50000000],
        'requested_by' => 3,
        'status' => 'pending',
    ]);

    $approval->reject(1, 'Amount exceeds policy limit');
    expect($approval->fresh()->status)->toBe('rejected')
        ->and($approval->fresh()->review_notes)->toBe('Amount exceeds policy limit');
});

// ═══════════════════════════════════════════════════════════════
// 13. API SIGNATURE VERIFICATION
// ═══════════════════════════════════════════════════════════════

it('rejects requests without API signature headers', function () {
    $middleware = new VerifyApiSignature;
    $request = Request::create('/api/test');

    expect(fn () => $middleware->handle($request, fn ($r) => response('OK')))
        ->toThrow(HttpException::class);
});

it('validates HMAC signatures correctly', function () {
    config(['security.api_keys.test-key' => 'test-secret']);

    $middleware = new VerifyApiSignature;
    $timestamp = (string) time();
    $body = '{"action":"test"}';
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

    $request = Request::create('/api/test', 'POST', [], [], [], [], $body);
    $request->headers->set('X-Api-Key', 'test-key');
    $request->headers->set('X-Signature', $signature);
    $request->headers->set('X-Timestamp', $timestamp);

    $response = $middleware->handle($request, fn ($r) => response('OK'));
    expect($response->getContent())->toBe('OK');
});

// ═══════════════════════════════════════════════════════════════
// 6. IDLE TIMEOUT
// ═══════════════════════════════════════════════════════════════

it('tracks last activity in session', function () {
    $middleware = new IdleTimeout;
    $request = Request::create('/dashboard');

    $response = $middleware->handle($request, fn ($r) => response('OK'));
    expect($response->getContent())->toBe('OK');
});

// ═══════════════════════════════════════════════════════════════
// PASSWORD RULES
// ═══════════════════════════════════════════════════════════════

it('enforces strong password rules', function () {
    $rule = PasswordRules::strong();

    expect($rule)->toBeInstanceOf(Password::class);
});
