<?php

use App\Models\Tenant\CrbInquiry;
use App\Models\Tenant\EclComputation;
use App\Models\Tenant\EclStaging;
use App\Models\Tenant\GroupMember;
use App\Models\Tenant\KycScreening;
use App\Models\Tenant\LendingGroup;
use App\Services\Tenant\CrbIntegrationService;
use App\Services\Tenant\EclService;
use App\Services\Tenant\GroupLendingService;
use App\Services\Tenant\KycService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->eclService = new EclService;
    $this->crbService = new CrbIntegrationService;
    $this->kycService = new KycService;
    $this->groupService = new GroupLendingService;
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 4.1: IFRS 9 & ECL
// ═══════════════════════════════════════════════════════════════

it('determines IFRS 9 stage from DPD', function () {
    expect(EclStaging::determineStage(0))->toBe(1)
        ->and(EclStaging::determineStage(15))->toBe(1)
        ->and(EclStaging::determineStage(31))->toBe(2)
        ->and(EclStaging::determineStage(60))->toBe(2)
        ->and(EclStaging::determineStage(91))->toBe(3)
        ->and(EclStaging::determineStage(180))->toBe(3);
});

it('stages a loan for ECL computation', function () {
    $staging = $this->eclService->stageLoan([
        'loan_id' => 1,
        'loan_number' => 'LN-001',
        'dpd' => 45,
        'ead' => 5000000,
        'computation_period' => '2026-03',
    ]);

    expect($staging)->toBeInstanceOf(EclStaging::class)
        ->and($staging->stage)->toBe(2) // 31-90 = Stage 2
        ->and((float) $staging->ecl_amount)->toBeGreaterThan(0);
});

it('computes ECL as PD × LGD × EAD', function () {
    $staging = EclStaging::create([
        'loan_id' => 1,
        'loan_number' => 'LN-ECL-01',
        'stage' => 1,
        'dpd' => 10,
        'pd' => 0.05,
        'lgd' => 0.45,
        'ead' => 10000000,
        'computation_period' => '2026-03',
    ]);

    $ecl = $staging->computeEcl();
    // 0.05 × 0.45 × 10000000 = 225000
    expect($ecl)->toBe(225000.0);
});

it('runs ECL computation for a period', function () {
    $this->eclService->stageLoan([
        'loan_id' => 1,
        'loan_number' => 'LN-01',
        'dpd' => 0,
        'pd' => 0.01,
        'lgd' => 0.45,
        'ead' => 5000000,
        'computation_period' => '2026-03',
    ]);
    $this->eclService->stageLoan([
        'loan_id' => 2,
        'loan_number' => 'LN-02',
        'dpd' => 50,
        'pd' => 0.15,
        'lgd' => 0.45,
        'ead' => 3000000,
        'computation_period' => '2026-03',
    ]);
    $this->eclService->stageLoan([
        'loan_id' => 3,
        'loan_number' => 'LN-03',
        'dpd' => 100,
        'pd' => 0.65,
        'lgd' => 0.45,
        'ead' => 2000000,
        'computation_period' => '2026-03',
    ]);

    $comp = $this->eclService->runComputation('2026-03');

    expect($comp)->toBeInstanceOf(EclComputation::class)
        ->and($comp->stage_1_count)->toBe(1)
        ->and($comp->stage_2_count)->toBe(1)
        ->and($comp->stage_3_count)->toBe(1)
        ->and((float) $comp->total_ead)->toBe(10000000.0)
        ->and((float) $comp->total_ecl)->toBeGreaterThan(0)
        ->and((float) $comp->coverage_ratio)->toBeGreaterThan(0);
});

it('posts ECL computation to GL', function () {
    $comp = EclComputation::create([
        'computation_period' => '2026-02',
        'computation_date' => '2026-02-28',
        'total_ead' => 10000000,
        'total_ecl' => 500000,
        'provision_amount' => 500000,
    ]);

    $comp->markPosted('JE-2026-001', 1);

    expect($comp->fresh()->is_posted)->toBeTrue()
        ->and($comp->fresh()->journal_reference)->toBe('JE-2026-001');
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 4.3: CRB INTEGRATION
// ═══════════════════════════════════════════════════════════════

it('submits a CRB credit score inquiry', function () {
    $inquiry = $this->crbService->inquire([
        'member_id' => 1,
        'member_name' => 'John Doe',
        'national_id' => 'CM123456789',
        'crb_name' => 'TransUnion',
        'credit_score' => 720,
    ]);

    expect($inquiry)->toBeInstanceOf(CrbInquiry::class)
        ->and($inquiry->inquiry_ref)->toStartWith('CRB-')
        ->and($inquiry->credit_score)->toBe(720)
        ->and($inquiry->risk_grade)->toBe('A')
        ->and($inquiry->status)->toBe(CrbInquiry::STATUS_COMPLETED);
});

it('grades risk from credit score correctly', function () {
    expect(CrbInquiry::gradeFromScore(850))->toBe('AA')
        ->and(CrbInquiry::gradeFromScore(700))->toBe('A')
        ->and(CrbInquiry::gradeFromScore(550))->toBe('B')
        ->and(CrbInquiry::gradeFromScore(400))->toBe('C')
        ->and(CrbInquiry::gradeFromScore(250))->toBe('D')
        ->and(CrbInquiry::gradeFromScore(100))->toBe('HR');
});

it('retrieves member credit history', function () {
    $this->crbService->inquire([
        'member_id' => 5,
        'member_name' => 'Jane',
        'credit_score' => 600,
    ]);
    $this->crbService->inquire([
        'member_id' => 5,
        'member_name' => 'Jane',
        'credit_score' => 650,
    ]);

    $history = $this->crbService->getMemberHistory(5);

    expect($history['total_inquiries'])->toBe(2)
        ->and($history['latest_score'])->toBeIn([600, 650]);
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 4.4: ENHANCED KYC
// ═══════════════════════════════════════════════════════════════

it('runs a KYC screening', function () {
    $screening = $this->kycService->runScreening([
        'member_id' => 1,
        'member_name' => 'John Doe',
        'screening_type' => KycScreening::TYPE_PEP,
        'kyc_tier' => 2,
        'result' => KycScreening::RESULT_CLEAR,
        'data_source' => 'pep_database',
    ]);

    expect($screening)->toBeInstanceOf(KycScreening::class)
        ->and($screening->screening_ref)->toStartWith('KYC-')
        ->and($screening->result)->toBe('clear');
});

it('detects high-risk screenings', function () {
    $this->kycService->runScreening([
        'member_id' => 1,
        'member_name' => 'Risky',
        'screening_type' => KycScreening::TYPE_SANCTIONS,
        'result' => KycScreening::RESULT_MATCH,
        'match_score' => 85.5,
    ]);
    $this->kycService->runScreening([
        'member_id' => 2,
        'member_name' => 'Clean',
        'screening_type' => KycScreening::TYPE_PEP,
        'result' => KycScreening::RESULT_CLEAR,
    ]);

    $highRisk = $this->kycService->getHighRiskScreenings();
    expect($highRisk)->toHaveCount(1)
        ->and($highRisk->first()->member_name)->toBe('Risky');
});

it('computes KYC completeness score', function () {
    // Tier 2 requires: id_verification, pep, sanctions
    $this->kycService->runScreening([
        'member_id' => 1,
        'member_name' => 'Test',
        'screening_type' => KycScreening::TYPE_ID_VERIFICATION,
        'result' => KycScreening::RESULT_CLEAR,
    ]);
    $this->kycService->runScreening([
        'member_id' => 1,
        'member_name' => 'Test',
        'screening_type' => KycScreening::TYPE_PEP,
        'result' => KycScreening::RESULT_CLEAR,
    ]);

    $completeness = $this->kycService->getKycCompleteness(1, requiredTier: 2);

    expect($completeness['tier'])->toBe(2)
        ->and($completeness['completed'])->toBe(2)
        ->and($completeness['total'])->toBe(3)
        ->and($completeness['score'])->toBe(66.7)
        ->and($completeness['has_id_verification'])->toBeTrue()
        ->and($completeness['has_sanctions'])->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 4.5: GROUP LENDING
// ═══════════════════════════════════════════════════════════════

it('creates a lending group', function () {
    $group = $this->groupService->createGroup([
        'group_name' => 'Sunrise Women Group',
        'branch_code' => 'BR-001',
        'liability_type' => LendingGroup::LIABILITY_JOINT,
        'max_members' => 20,
        'min_members' => 5,
    ]);

    expect($group)->toBeInstanceOf(LendingGroup::class)
        ->and($group->group_code)->toStartWith('GRP-')
        ->and($group->status)->toBe('active');
});

it('adds a member to a group', function () {
    $group = $this->groupService->createGroup([
        'group_name' => 'Test Group',
        'max_members' => 10,
    ]);

    $member = $this->groupService->addMember($group->id, [
        'member_id' => 1,
        'member_name' => 'Alice',
        'role' => 'chairperson',
    ]);

    expect($member)->toBeInstanceOf(GroupMember::class)
        ->and($member->role)->toBe('chairperson')
        ->and($member->status)->toBe('active');
});

it('rejects member when group is full', function () {
    $group = $this->groupService->createGroup([
        'group_name' => 'Tiny Group',
        'max_members' => 1,
    ]);

    $this->groupService->addMember($group->id, [
        'member_id' => 1,
        'member_name' => 'First',
    ]);

    expect(fn () => $this->groupService->addMember($group->id, [
        'member_id' => 2,
        'member_name' => 'Second',
    ]))->toThrow(RuntimeException::class, 'maximum capacity');
});

it('computes group performance', function () {
    $group = $this->groupService->createGroup([
        'group_name' => 'Perf Group',
        'max_members' => 10,
    ]);

    $this->groupService->addMember($group->id, [
        'member_id' => 1,
        'member_name' => 'A',
        'personal_repayment_rate' => 95,
        'total_borrowed' => 1000000,
        'total_repaid' => 950000,
    ]);
    $this->groupService->addMember($group->id, [
        'member_id' => 2,
        'member_name' => 'B',
        'personal_repayment_rate' => 85,
        'total_borrowed' => 500000,
        'total_repaid' => 425000,
    ]);

    $perf = $this->groupService->getGroupPerformance($group->id);

    expect($perf['member_count'])->toBe(2)
        ->and($perf['avg_repayment_rate'])->toBe(90.0)
        ->and($perf['total_borrowed'])->toBe(1500000.0);
});

it('advances group lending cycle', function () {
    $group = $this->groupService->createGroup([
        'group_name' => 'Cycle Group',
        'cycle_number' => 1,
    ]);

    expect($group->cycle_number)->toBe(1);

    $advanced = $this->groupService->advanceCycle($group->id);
    expect($advanced->cycle_number)->toBe(2);
});

it('computes member default rate', function () {
    $group = $this->groupService->createGroup(['group_name' => 'Default Rate Group']);

    $member = GroupMember::create([
        'group_id' => $group->id,
        'member_id' => 1,
        'member_name' => 'Test',
        'loans_taken' => 10,
        'loans_defaulted' => 2,
        'status' => 'active',
    ]);

    expect($member->default_rate)->toBe(20.0);
});
