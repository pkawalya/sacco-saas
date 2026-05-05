<?php

use App\Models\Tenant\Member;
use App\Models\Tenant\MemberDocument;
use App\Models\Tenant\MemberShare;
use App\Models\Tenant\MemberStateHistory;
use App\Services\MemberNumberGenerator;
use Illuminate\Database\QueryException;

// Ensure tenant context is initialized for all member tests
beforeEach(function () {
    $this->initializeTenancy();
});

// ─── FR-MM CRUD ─────────────────────────────────────────────

it('can create a member with all required fields', function () {
    $member = Member::factory()->create([
        'first_name' => 'Alice',
        'middle_name' => null,
        'last_name' => 'Nakato',
        'status' => Member::STATUS_ACTIVE,
    ]);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->full_name)->toBe('Alice Nakato')
        ->and($member->status)->toBe(Member::STATUS_ACTIVE)
        ->and($member->id)->toBeGreaterThan(0);
});

it('full_name accessor concatenates first, middle, and last names', function () {
    $member = Member::factory()->make([
        'first_name' => 'Jane',
        'middle_name' => 'Apio',
        'last_name' => 'Ouma',
    ]);

    expect($member->full_name)->toBe('Jane Apio Ouma');
});

it('full_name omits empty middle name', function () {
    $member = Member::factory()->make([
        'first_name' => 'John',
        'middle_name' => null,
        'last_name' => 'Doe',
    ]);

    expect($member->full_name)->toBe('John Doe');
});

it('can list multiple members', function () {
    $baseline = Member::count();
    Member::factory()->count(5)->create();

    expect(Member::count())->toBe($baseline + 5);
});

it('can update a member', function () {
    $member = Member::factory()->create(['occupation' => 'Farmer']);

    $member->update(['occupation' => 'Teacher']);

    expect($member->fresh()->occupation)->toBe('Teacher');
});

it('soft-deletes a member', function () {
    $member = Member::factory()->create();
    $id = $member->id;

    $member->delete();

    expect(Member::find($id))->toBeNull()
        ->and(Member::withTrashed()->find($id))->not->toBeNull();
});

// ─── FR-MM-003: Unique national ID ──────────────────────────

it('enforces unique national_id_number at database level', function () {
    Member::factory()->create(['national_id_number' => 'CM12345678901234']);

    expect(fn () => Member::factory()->create(['national_id_number' => 'CM12345678901234']))
        ->toThrow(QueryException::class);
});

it('enforces unique member_number at database level', function () {
    Member::factory()->create(['member_number' => 'KLA-2026-0001']);

    expect(fn () => Member::factory()->create(['member_number' => 'KLA-2026-0001']))
        ->toThrow(QueryException::class);
});

// ─── FR-MM-004: Member number generation ────────────────────

it('generates correctly formatted member numbers', function () {
    $number = MemberNumberGenerator::generate('KLA');

    expect($number)->toMatch('/^KLA-\d{4}-\d+$/');
});

it('generates sequential member numbers for the same branch and year', function () {
    $branch = 'TST';

    $first = MemberNumberGenerator::generate($branch);
    Member::factory()->create(['member_number' => $first, 'branch_code' => $branch]);

    $second = MemberNumberGenerator::generate($branch);

    expect($first)->not->toBe($second);
});

it('generates different sequences per branch', function () {
    $kla = MemberNumberGenerator::generate('KLA');
    $mbr = MemberNumberGenerator::generate('MBR');

    expect(substr($kla, 0, 3))->toBe('KLA')
        ->and(substr($mbr, 0, 3))->toBe('MBR');
});

// ─── FR-MM-007: KYC score computation ───────────────────────

it('computes kyc score from verified documents', function () {
    $member = Member::factory()->create(['kyc_score' => 0]);

    // Create verified national_id (30) + photograph (20) = 50
    MemberDocument::create([
        'member_id' => $member->id,
        'document_type' => 'national_id',
        'file_path' => 'test/id.pdf',
        'upload_date' => now(),
        'verification_status' => 'verified',
    ]);

    MemberDocument::create([
        'member_id' => $member->id,
        'document_type' => 'photograph',
        'file_path' => 'test/photo.jpg',
        'upload_date' => now(),
        'verification_status' => 'verified',
    ]);

    $member->recalculateKycScore();

    expect($member->fresh()->kyc_score)->toBe(50);
});

it('does not count pending documents in kyc score', function () {
    $member = Member::factory()->create(['kyc_score' => 0]);

    MemberDocument::create([
        'member_id' => $member->id,
        'document_type' => 'national_id',
        'file_path' => 'test/id.pdf',
        'upload_date' => now(),
        'verification_status' => 'pending',
    ]);

    $member->recalculateKycScore();

    expect($member->fresh()->kyc_score)->toBe(0);
});

it('caps kyc score at 100', function () {
    $member = Member::factory()->create(['kyc_score' => 0]);

    // All 6 document types verified = 30+20+15+15+10+10 = 100
    $types = ['national_id', 'photograph', 'utility_bill', 'employer_letter', 'signature_card', 'application_form'];

    foreach ($types as $type) {
        MemberDocument::create([
            'member_id' => $member->id,
            'document_type' => $type,
            'file_path' => "test/{$type}.pdf",
            'upload_date' => now(),
            'verification_status' => 'verified',
        ]);
    }

    $member->recalculateKycScore();

    expect($member->fresh()->kyc_score)->toBe(100);
});

it('kyc_complete accessor returns true when score meets threshold', function () {
    $member = Member::factory()->make(['kyc_score' => 80, 'kyc_threshold' => 70]);

    expect($member->is_kyc_complete)->toBeTrue();
});

it('kyc_complete accessor returns false when score is below threshold', function () {
    $member = Member::factory()->make(['kyc_score' => 60, 'kyc_threshold' => 70]);

    expect($member->is_kyc_complete)->toBeFalse();
});

// ─── FR-MM-010: Exit block validation ───────────────────────

it('allows exit when member has no obligations', function () {
    $member = Member::factory()->create();

    $blocks = $member->getExitBlockReasons();

    expect($blocks)->toBeArray()->toBeEmpty();
});

it('blocks exit when member has share capital outstanding', function () {
    $member = Member::factory()->create();

    MemberShare::create([
        'member_id' => $member->id,
        'shares_held' => 10,
        'par_value' => 10000,
        'total_value' => 100000,
        'percentage_of_total' => 0.001,
    ]);

    $blocks = $member->getExitBlockReasons();

    expect($blocks)->not->toBeEmpty()
        ->and($blocks[0])->toContain('share');
});

// ─── FR-MM-012: Lifecycle state transitions ──────────────────

it('transitions member state and logs to state history', function () {
    $member = Member::factory()->create(['status' => Member::STATUS_APPLICANT]);

    $member->transitionTo(
        newState: Member::STATUS_ACTIVE,
        reasonCode: 'kyc_approved',
        notes: 'All documents verified',
        actedBy: 1
    );

    expect($member->fresh()->status)->toBe(Member::STATUS_ACTIVE)
        ->and(MemberStateHistory::where('member_id', $member->id)->count())->toBe(1);

    $history = MemberStateHistory::where('member_id', $member->id)->first();

    expect($history->from_state)->toBe(Member::STATUS_APPLICANT)
        ->and($history->to_state)->toBe(Member::STATUS_ACTIVE)
        ->and($history->reason_code)->toBe('kyc_approved')
        ->and($history->acted_by)->toBe(1);
});

it('creates sequential history entries across multiple transitions', function () {
    $member = Member::factory()->create(['status' => Member::STATUS_APPLICANT]);

    $member->transitionTo(Member::STATUS_ACTIVE, 'kyc_approved');
    $member->transitionTo(Member::STATUS_DORMANT, 'auto_dormancy');
    $member->transitionTo(Member::STATUS_ACTIVE, 'reactivated');

    expect(MemberStateHistory::where('member_id', $member->id)->count())->toBe(3);
});

// ─── Scopes ──────────────────────────────────────────────────

it('active scope filters to active members only', function () {
    $baseline = Member::active()->count();
    Member::factory()->count(3)->active()->create();
    Member::factory()->count(2)->applicant()->create();
    Member::factory()->dormant()->create();

    expect(Member::active()->count())->toBe($baseline + 3);
});

it('byBranch scope filters by branch code', function () {
    $baseKla = Member::byBranch('KLA')->count();
    $baseMbr = Member::byBranch('MBR')->count();
    Member::factory()->count(3)->create(['branch_code' => 'KLA']);
    Member::factory()->count(2)->create(['branch_code' => 'MBR']);

    expect(Member::byBranch('KLA')->count())->toBe($baseKla + 3)
        ->and(Member::byBranch('MBR')->count())->toBe($baseMbr + 2);
});

it('kycIncomplete scope returns members below threshold', function () {
    $baseline = Member::kycIncomplete()->count();
    Member::factory()->count(3)->create(['kyc_score' => 40, 'kyc_threshold' => 70]);
    Member::factory()->count(2)->create(['kyc_score' => 80, 'kyc_threshold' => 70]);

    expect(Member::kycIncomplete()->count())->toBe($baseline + 3);
});
