<?php

use App\Models\Tenant\CollectionsActivity;
use App\Models\Tenant\CollectionsWorklist;
use App\Models\Tenant\DemandLetter;
use App\Models\Tenant\LegalCase;
use App\Models\Tenant\PtpRecord;
use App\Services\Tenant\CollectionsService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->colService = new CollectionsService;
});

// ─── Helpers ────────────────────────────────────────────────────

function createWorklistEntry(array $overrides = []): CollectionsWorklist
{
    static $counter = 0;
    $counter++;

    return CollectionsWorklist::create(array_merge([
        'loan_id' => $counter,
        'loan_number' => 'LN-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
        'member_name' => 'Member '.$counter,
        'dpd' => 0,
        'arrears_amount' => 0,
        'outstanding_balance' => 1000000,
        'instalment_amount' => 100000,
        'delinquency_bucket' => 'current',
        'tier' => CollectionsWorklist::TIER_OFFICER,
        'status' => CollectionsWorklist::STATUS_ACTIVE,
    ], $overrides));
}

// ─── FR-CE-001: Delinquency reclassification ────────────────

it('classifies DPD into correct buckets', function () {
    expect(CollectionsWorklist::classifyBucket(0))->toBe('current')
        ->and(CollectionsWorklist::classifyBucket(15))->toBe('1-30')
        ->and(CollectionsWorklist::classifyBucket(45))->toBe('31-60')
        ->and(CollectionsWorklist::classifyBucket(75))->toBe('61-90')
        ->and(CollectionsWorklist::classifyBucket(120))->toBe('91-180')
        ->and(CollectionsWorklist::classifyBucket(200))->toBe('180+');
});

it('reclassifies bucket on DPD change', function () {
    $entry = createWorklistEntry(['dpd' => 5, 'delinquency_bucket' => 'current']);
    $entry->update(['dpd' => 45]);
    $entry->reclassify();

    expect($entry->fresh()->delinquency_bucket)->toBe('31-60');
});

// ─── FR-CE-002: Penalty computation ─────────────────────────

it('computes daily penalty correctly', function () {
    $entry = createWorklistEntry([
        'dpd' => 10,
        'arrears_amount' => 500000,
        'penalty_rate' => 5.00,  // 5% p.a.
    ]);

    $daily = $entry->computeDailyPenalty();

    // 500000 * (5/100/365) = ~68.49
    expect($daily)->toBe(68.49);
});

it('accrues penalty incrementally', function () {
    $entry = createWorklistEntry([
        'dpd' => 10,
        'arrears_amount' => 365000,
        'penalty_rate' => 10.00,  // 10% p.a.
        'accrued_penalty' => 0,
    ]);

    $entry->accruePenalty();
    $entry->accruePenalty();

    // daily = 365000 * (10/100/365) = 100 per day, 2 days = 200
    expect((float) $entry->fresh()->accrued_penalty)->toBe(200.0);
});

it('returns zero penalty when rate is zero', function () {
    $entry = createWorklistEntry([
        'dpd' => 30,
        'arrears_amount' => 500000,
        'penalty_rate' => 0,
    ]);

    expect($entry->computeDailyPenalty())->toBe(0.0);
});

// ─── FR-CE-003: Worklist per officer ────────────────────────

it('retrieves officer worklist sorted by DPD descending', function () {
    createWorklistEntry(['dpd' => 10, 'officer_id' => 1, 'officer_name' => 'Alice']);
    createWorklistEntry(['dpd' => 60, 'officer_id' => 1, 'officer_name' => 'Alice']);
    createWorklistEntry(['dpd' => 30, 'officer_id' => 1, 'officer_name' => 'Alice']);
    createWorklistEntry(['dpd' => 90, 'officer_id' => 2, 'officer_name' => 'Bob']);

    $list = $this->colService->getOfficerWorklist(1);

    expect($list)->toHaveCount(3)
        ->and($list->first()->dpd)->toBe(60)
        ->and($list->last()->dpd)->toBe(10);
});

// ─── FR-CE-004: Auto-escalation ─────────────────────────────

it('auto-escalates based on DPD thresholds', function () {
    $entry = createWorklistEntry(['dpd' => 35, 'tier' => CollectionsWorklist::TIER_OFFICER]);

    expect($entry->determineEscalationTier())->toBe(CollectionsWorklist::TIER_SUPERVISOR);

    $escalated = $entry->autoEscalate();

    expect($escalated)->toBeTrue()
        ->and($entry->fresh()->tier)->toBe(CollectionsWorklist::TIER_SUPERVISOR)
        ->and($entry->fresh()->previous_tier)->toBe(CollectionsWorklist::TIER_OFFICER)
        ->and($entry->fresh()->escalated_at)->not->toBeNull();
});

it('does not escalate when DPD is within current tier', function () {
    $entry = createWorklistEntry(['dpd' => 25, 'tier' => CollectionsWorklist::TIER_OFFICER]);

    expect($entry->autoEscalate())->toBeFalse();
});

it('escalates to legal tier for 180+ DPD', function () {
    $entry = createWorklistEntry(['dpd' => 200, 'tier' => CollectionsWorklist::TIER_OFFICER]);

    $entry->autoEscalate();

    expect($entry->fresh()->tier)->toBe(CollectionsWorklist::TIER_LEGAL);
});

// ─── FR-CE-001+002+004: EOD batch processing ───────────────

it('runs daily EOD processing with all components', function () {
    createWorklistEntry([
        'dpd' => 35,
        'delinquency_bucket' => '1-30',  // should reclassify to 31-60
        'arrears_amount' => 365000,
        'penalty_rate' => 10.00,
        'tier' => CollectionsWorklist::TIER_OFFICER,  // should escalate to supervisor
    ]);

    $stats = $this->colService->runDailyEod();

    expect($stats['reclassified'])->toBe(1)
        ->and($stats['penalties_accrued'])->toBe(1)
        ->and($stats['escalated'])->toBe(1);
});

// ─── FR-CE-010: Activity logging ────────────────────────────

it('logs a collections activity', function () {
    $entry = createWorklistEntry();

    $activity = $this->colService->logActivity($entry->id, [
        'activity_type' => CollectionsActivity::TYPE_CALL,
        'description' => 'Called member regarding overdue payment',
        'outcome' => CollectionsActivity::OUTCOME_CONTACTED,
        'officer_id' => 1,
        'officer_name' => 'Alice',
    ]);

    expect($activity)->toBeInstanceOf(CollectionsActivity::class)
        ->and($activity->loan_number)->toBe($entry->loan_number)
        ->and($activity->activity_type)->toBe('call')
        ->and($activity->outcome)->toBe('contacted');
});

// ─── FR-CE-011: PTP capture & broken promise flag ───────────

it('captures a PTP and logs activity', function () {
    $entry = createWorklistEntry(['dpd' => 15, 'arrears_amount' => 200000]);

    $ptp = $this->colService->capturePtp($entry->id, [
        'promised_amount' => 200000,
        'promised_date' => now()->addDays(7)->toDateString(),
        'captured_by' => 1,
        'officer_name' => 'Alice',
    ]);

    expect($ptp)->toBeInstanceOf(PtpRecord::class)
        ->and($ptp->status)->toBe(PtpRecord::STATUS_PENDING)
        ->and($ptp->is_broken)->toBeFalse()
        ->and($entry->activities()->count())->toBe(1);
});

it('flags overdue PTPs as broken', function () {
    $entry = createWorklistEntry();

    PtpRecord::create([
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'promised_amount' => 100000,
        'promised_date' => now()->subDays(3)->toDateString(),  // overdue
        'status' => PtpRecord::STATUS_PENDING,
    ]);

    $broken = $this->colService->flagBrokenPtps();

    expect($broken)->toBe(1);
    $ptp = PtpRecord::first();
    expect($ptp->status)->toBe(PtpRecord::STATUS_BROKEN)
        ->and($ptp->is_broken)->toBeTrue()
        ->and($ptp->broken_flagged_at)->not->toBeNull();
});

it('records payment against PTP and marks as kept', function () {
    $entry = createWorklistEntry();

    $ptp = PtpRecord::create([
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'promised_amount' => 150000,
        'promised_date' => now()->addDays(5)->toDateString(),
        'status' => PtpRecord::STATUS_PENDING,
    ]);

    $ptp->recordPayment(150000);

    expect($ptp->fresh()->status)->toBe(PtpRecord::STATUS_KEPT)
        ->and((float) $ptp->fresh()->actual_amount_paid)->toBe(150000.0);
});

it('marks partial payment on PTP', function () {
    $entry = createWorklistEntry();

    $ptp = PtpRecord::create([
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'promised_amount' => 200000,
        'promised_date' => now()->addDays(5)->toDateString(),
        'status' => PtpRecord::STATUS_PENDING,
    ]);

    $ptp->recordPayment(80000);

    expect($ptp->fresh()->status)->toBe(PtpRecord::STATUS_PARTIAL)
        ->and((float) $ptp->fresh()->actual_amount_paid)->toBe(80000.0);
});

// ─── FR-CE-012: PTP performance metrics ─────────────────────

it('computes PTP performance metrics per officer', function () {
    $entry = createWorklistEntry();

    PtpRecord::create([
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'promised_amount' => 100000,
        'promised_date' => now()->subDay()->toDateString(),
        'status' => PtpRecord::STATUS_KEPT,
        'officer_name' => 'Alice',
    ]);
    PtpRecord::create([
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'promised_amount' => 200000,
        'promised_date' => now()->subDay()->toDateString(),
        'status' => PtpRecord::STATUS_BROKEN,
        'is_broken' => true,
        'officer_name' => 'Alice',
    ]);

    $metrics = $this->colService->getPtpPerformanceByOfficer();

    expect($metrics)->toHaveCount(1);
    $alice = $metrics->first();
    expect($alice['total'])->toBe(2)
        ->and($alice['kept'])->toBe(1)
        ->and($alice['broken'])->toBe(1)
        ->and($alice['kept_rate'])->toBe(50.0);
});

// ─── FR-CE-013: Demand letters ──────────────────────────────

it('generates a demand letter and logs activity', function () {
    $entry = createWorklistEntry(['dpd' => 45, 'arrears_amount' => 500000]);

    $letter = $this->colService->generateDemandLetter($entry->id, DemandLetter::TYPE_FIRST_DEMAND);

    expect($letter)->toBeInstanceOf(DemandLetter::class)
        ->and($letter->letter_type)->toBe('first_demand')
        ->and($letter->recipient_name)->toBe($entry->member_name)
        ->and($letter->status)->toBe(DemandLetter::STATUS_DRAFT)
        ->and($letter->reference_number)->toStartWith('DL-')
        ->and($entry->activities()->count())->toBe(1);
});

it('marks a demand letter as sent', function () {
    $entry = createWorklistEntry();

    $letter = DemandLetter::create([
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'letter_type' => DemandLetter::TYPE_REMINDER,
        'reference_number' => 'DL-TEST-001',
        'recipient_name' => 'Test',
        'status' => DemandLetter::STATUS_DRAFT,
    ]);

    $letter->markSent('2026-03-13');

    expect($letter->fresh()->status)->toBe(DemandLetter::STATUS_SENT)
        ->and($letter->fresh()->sent_date->toDateString())->toBe('2026-03-13');
});

// ─── FR-CE-030: PAR aging report ────────────────────────────

it('generates a PAR aging report', function () {
    createWorklistEntry(['dpd' => 0, 'delinquency_bucket' => 'current', 'outstanding_balance' => 500000, 'arrears_amount' => 0]);
    createWorklistEntry(['dpd' => 20, 'delinquency_bucket' => '1-30', 'outstanding_balance' => 300000, 'arrears_amount' => 100000]);
    createWorklistEntry(['dpd' => 50, 'delinquency_bucket' => '31-60', 'outstanding_balance' => 200000, 'arrears_amount' => 150000]);

    $report = $this->colService->getParAgingReport();

    expect($report['total_outstanding'])->toBe(1000000.0)
        ->and($report['total_par'])->toBe(500000.0)  // 300k + 200k (non-current)
        ->and($report['par_ratio'])->toBe(50.0)
        ->and($report['buckets'])->toHaveCount(6);  // all 6 buckets represented
});

// ─── FR-CE-031: Collector scorecard ─────────────────────────

it('generates a collector scorecard', function () {
    createWorklistEntry(['dpd' => 20, 'arrears_amount' => 200000, 'officer_name' => 'Alice', 'officer_id' => 1, 'status' => CollectionsWorklist::STATUS_ACTIVE]);
    createWorklistEntry(['dpd' => 0, 'arrears_amount' => 150000, 'officer_name' => 'Alice', 'officer_id' => 1, 'status' => CollectionsWorklist::STATUS_RESOLVED, 'resolved_at' => now()]);

    $scorecard = $this->colService->getCollectorScorecard();

    expect($scorecard)->toHaveCount(1);
    $alice = $scorecard->first();
    expect($alice['officer_name'])->toBe('Alice')
        ->and($alice['assigned'])->toBe(1)
        ->and($alice['resolved'])->toBe(1)
        ->and($alice['collection_rate'])->toBe(50.0);
});

// ─── Legal case ─────────────────────────────────────────────

it('tracks legal case recovery rate', function () {
    $entry = createWorklistEntry(['dpd' => 200, 'status' => CollectionsWorklist::STATUS_LEGAL]);

    $legalCase = LegalCase::create([
        'case_ref' => 'LC-2026-001',
        'worklist_id' => $entry->id,
        'loan_id' => $entry->loan_id,
        'loan_number' => $entry->loan_number,
        'filing_date' => '2026-01-15',
        'claim_amount' => 1000000,
        'recovered_amount' => 250000,
        'status' => LegalCase::STATUS_EXECUTION,
    ]);

    expect($legalCase->recovery_rate)->toBe(25.0);
});

// ─── Resolve worklist ───────────────────────────────────────

it('resolves a worklist entry', function () {
    $entry = createWorklistEntry(['dpd' => 10]);

    $entry->resolve();

    expect($entry->fresh()->status)->toBe(CollectionsWorklist::STATUS_RESOLVED)
        ->and($entry->fresh()->resolved_at)->not->toBeNull();
});
