<?php

use App\Models\Tenant\AmlAlert;
use App\Models\Tenant\CrbSubmission;
use App\Models\Tenant\RegulatoryReturn;
use App\Models\Tenant\StrReport;
use App\Models\Tenant\TaxCalendar;
use App\Services\Tenant\RegulatoryComplianceService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->rcService = new RegulatoryComplianceService;
});

// ─── FR-RC-001: Return generation ───────────────────────────

it('generates a regulatory return', function () {
    $ret = $this->rcService->generateReturn([
        'return_name' => 'Prudential Return Q1 2026',
        'return_type' => RegulatoryReturn::TYPE_PRUDENTIAL,
        'period' => RegulatoryReturn::PERIOD_QUARTERLY,
        'fiscal_year' => 2026,
        'period_number' => 1,
        'period_start' => '2026-01-01',
        'period_end' => '2026-03-31',
        'due_date' => '2026-04-15',
    ]);

    expect($ret)->toBeInstanceOf(RegulatoryReturn::class)
        ->and($ret->return_code)->toStartWith('RR-PRUDENTIAL-')
        ->and($ret->status)->toBe(RegulatoryReturn::STATUS_PENDING);
});

// ─── FR-RC-002: Deadline tracking ───────────────────────────

it('detects overdue returns', function () {
    RegulatoryReturn::create([
        'return_code' => 'RR-OVER-001',
        'return_name' => 'Overdue Return',
        'return_type' => 'prudential',
        'period' => 'monthly',
        'fiscal_year' => 2026,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'due_date' => now()->subDays(5)->toDateString(),
        'status' => RegulatoryReturn::STATUS_PENDING,
    ]);

    expect($this->rcService->flagOverdueReturns())->toBe(1);
    expect(RegulatoryReturn::first()->status)->toBe(RegulatoryReturn::STATUS_OVERDUE);
});

it('gets upcoming returns due within N days', function () {
    RegulatoryReturn::create([
        'return_code' => 'RR-DUE-001',
        'return_name' => 'Due Soon',
        'return_type' => 'statistical',
        'period' => 'monthly',
        'fiscal_year' => 2026,
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'due_date' => now()->addDays(5)->toDateString(),
        'status' => RegulatoryReturn::STATUS_PENDING,
    ]);

    RegulatoryReturn::create([
        'return_code' => 'RR-FAR-001',
        'return_name' => 'Far Away',
        'return_type' => 'tax',
        'period' => 'quarterly',
        'fiscal_year' => 2026,
        'period_start' => '2026-01-01',
        'period_end' => '2026-03-31',
        'due_date' => now()->addDays(30)->toDateString(),
        'status' => RegulatoryReturn::STATUS_PENDING,
    ]);

    $upcoming = $this->rcService->getUpcomingReturns(7);
    expect($upcoming)->toHaveCount(1)
        ->and($upcoming->first()->return_name)->toBe('Due Soon');
});

it('checks if reminder is due', function () {
    $ret = RegulatoryReturn::create([
        'return_code' => 'RR-REM-001',
        'return_name' => 'Reminder Test',
        'return_type' => 'prudential',
        'period' => 'monthly',
        'fiscal_year' => 2026,
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'due_date' => now()->addDays(3)->toDateString(),
        'reminder_days_before' => 5,
        'status' => RegulatoryReturn::STATUS_PENDING,
    ]);

    expect($ret->isReminderDue())->toBeTrue();
});

// ─── FR-RC-003: Filing register ─────────────────────────────

it('marks a return as submitted', function () {
    $ret = RegulatoryReturn::create([
        'return_code' => 'RR-FILE-001',
        'return_name' => 'Filing Test',
        'return_type' => 'prudential',
        'period' => 'monthly',
        'fiscal_year' => 2026,
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'due_date' => '2026-03-15',
        'status' => RegulatoryReturn::STATUS_PENDING,
    ]);

    $ret->markSubmitted(userId: 1, reference: 'FIA-2026-001');

    expect($ret->fresh()->status)->toBe(RegulatoryReturn::STATUS_SUBMITTED)
        ->and($ret->fresh()->filing_reference)->toBe('FIA-2026-001')
        ->and($ret->fresh()->filed_by)->toBe(1);
});

it('provides a filing compliance dashboard', function () {
    RegulatoryReturn::create([
        'return_code' => 'RR-DASH-001',
        'return_name' => 'A',
        'return_type' => 'prudential',
        'period' => 'monthly',
        'fiscal_year' => 2026,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'due_date' => '2026-02-15',
        'status' => RegulatoryReturn::STATUS_ACCEPTED,
    ]);
    RegulatoryReturn::create([
        'return_code' => 'RR-DASH-002',
        'return_name' => 'B',
        'return_type' => 'statistical',
        'period' => 'monthly',
        'fiscal_year' => 2026,
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'due_date' => '2026-03-15',
        'status' => RegulatoryReturn::STATUS_PENDING,
    ]);

    $dash = $this->rcService->getFilingDashboard(2026);

    expect($dash['total'])->toBe(2)
        ->and($dash['accepted'])->toBe(1)
        ->and($dash['pending'])->toBe(1)
        ->and($dash['compliance_rate'])->toBe(50.0);
});

// ─── FR-RC-010: AML alert generation ───────────────────────

it('raises an AML alert', function () {
    $alert = $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_THRESHOLD,
        'member_name' => 'John Doe',
        'member_id' => 1,
        'transaction_amount' => 15000000,
        'severity' => AmlAlert::SEVERITY_HIGH,
        'risk_score' => 75,
    ]);

    expect($alert)->toBeInstanceOf(AmlAlert::class)
        ->and($alert->alert_id)->toStartWith('AML-')
        ->and($alert->status)->toBe(AmlAlert::STATUS_NEW)
        ->and($alert->severity)->toBe('high');
});

// ─── FR-RC-011: Alert queue ─────────────────────────────────

it('retrieves AML alert queue sorted by severity', function () {
    $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_THRESHOLD,
        'member_name' => 'Low Risk',
        'severity' => AmlAlert::SEVERITY_LOW,
        'risk_score' => 20,
    ]);
    $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_PEP,
        'member_name' => 'Critical',
        'severity' => AmlAlert::SEVERITY_CRITICAL,
        'risk_score' => 95,
    ]);
    $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_STRUCTURING,
        'member_name' => 'Medium',
        'severity' => AmlAlert::SEVERITY_MEDIUM,
        'risk_score' => 50,
    ]);

    $queue = $this->rcService->getAlertQueue();

    expect($queue)->toHaveCount(3)
        ->and($queue->first()->severity)->toBe('critical')
        ->and($queue->last()->severity)->toBe('low');
});

it('escalates an AML alert', function () {
    $alert = $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_SANCTIONS,
        'member_name' => 'Sanctioned Entity',
        'severity' => AmlAlert::SEVERITY_CRITICAL,
        'risk_score' => 100,
    ]);

    $alert->escalate(1);

    expect($alert->fresh()->status)->toBe(AmlAlert::STATUS_ESCALATED)
        ->and($alert->fresh()->is_escalated)->toBeTrue();
});

it('clears an AML alert with notes', function () {
    $alert = $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_UNUSUAL,
        'member_name' => 'False Positive',
        'severity' => AmlAlert::SEVERITY_LOW,
        'risk_score' => 15,
    ]);

    $alert->clear(1, 'Verified as normal business pattern');

    expect($alert->fresh()->status)->toBe(AmlAlert::STATUS_CLEARED)
        ->and($alert->fresh()->review_notes)->toContain('normal business');
});

// ─── FR-RC-012–013: STR/CTR generation ──────────────────────

it('generates an STR from an AML alert', function () {
    $alert = $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_STRUCTURING,
        'member_name' => 'Suspicious Member',
        'member_id' => 5,
        'transaction_amount' => 9800000,
        'severity' => AmlAlert::SEVERITY_HIGH,
        'risk_score' => 80,
    ]);

    $str = $this->rcService->generateStr($alert->id);

    expect($str)->toBeInstanceOf(StrReport::class)
        ->and($str->str_reference)->toStartWith('STR-')
        ->and($str->member_name)->toBe('Suspicious Member')
        ->and($str->report_type)->toBe('str')
        ->and($str->status)->toBe(StrReport::STATUS_DRAFT)
        ->and($alert->fresh()->status)->toBe(AmlAlert::STATUS_STR_FILED);
});

it('submits an STR', function () {
    $alert = $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_THRESHOLD,
        'member_name' => 'Test',
        'severity' => 'medium',
        'risk_score' => 50,
        'transaction_amount' => 20000000,
    ]);

    $str = $this->rcService->generateStr($alert->id);
    $str->submit(userId: 1);

    expect($str->fresh()->status)->toBe(StrReport::STATUS_SUBMITTED)
        ->and($str->fresh()->filed_date)->not->toBeNull();
});

it('provides an AML dashboard', function () {
    $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_THRESHOLD,
        'member_name' => 'A',
        'severity' => 'high',
        'risk_score' => 70,
    ]);
    $alert = $this->rcService->raiseAmlAlert([
        'rule_triggered' => AmlAlert::RULE_STRUCTURING,
        'member_name' => 'B',
        'severity' => 'medium',
        'risk_score' => 50,
    ]);
    $alert->clear(1, 'OK');

    $dash = $this->rcService->getAmlDashboard();

    expect($dash['total_alerts'])->toBe(2)
        ->and($dash['unresolved'])->toBe(1)
        ->and($dash['high_risk'])->toBe(1);
});

// ─── FR-RC-020: CRB submission ──────────────────────────────

it('creates a CRB submission', function () {
    $sub = $this->rcService->createCrbSubmission([
        'submission_date' => '2026-03-15',
        'period' => 'monthly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'record_count' => 500,
        'positive_records' => 450,
        'negative_records' => 50,
        'crb_name' => 'TransUnion',
    ]);

    expect($sub)->toBeInstanceOf(CrbSubmission::class)
        ->and($sub->submission_ref)->toStartWith('CRB-')
        ->and($sub->npl_ratio)->toBe(10.0);
});

// ─── FR-RC-021–022: Tax calendar ────────────────────────────

it('tracks tax obligations with balance due', function () {
    $tax = TaxCalendar::create([
        'tax_type' => TaxCalendar::TAX_PAYE,
        'description' => 'PAYE February 2026',
        'fiscal_year' => 2026,
        'period_month' => 2,
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'due_date' => '2026-03-15',
        'computed_amount' => 5000000,
        'paid_amount' => 0,
        'filing_status' => TaxCalendar::STATUS_DUE,
    ]);

    expect($tax->balance_due)->toBe(5000000.0);

    $tax->markPaid(5000000, 'URA-2026-001');

    expect($tax->fresh()->filing_status)->toBe(TaxCalendar::STATUS_PAID)
        ->and($tax->fresh()->receipt_number)->toBe('URA-2026-001');
});

it('flags overdue tax obligations', function () {
    TaxCalendar::create([
        'tax_type' => TaxCalendar::TAX_VAT,
        'description' => 'VAT Overdue',
        'fiscal_year' => 2026,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'due_date' => now()->subDays(10)->toDateString(),
        'filing_status' => TaxCalendar::STATUS_DUE,
    ]);

    expect($this->rcService->flagOverdueTaxes())->toBe(1);
    expect(TaxCalendar::first()->filing_status)->toBe(TaxCalendar::STATUS_OVERDUE);
});

it('provides a tax summary for a fiscal year', function () {
    TaxCalendar::create([
        'tax_type' => TaxCalendar::TAX_PAYE,
        'description' => 'PAYE Jan',
        'fiscal_year' => 2026,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'due_date' => '2026-02-15',
        'computed_amount' => 3000000,
        'paid_amount' => 3000000,
        'filing_status' => TaxCalendar::STATUS_PAID,
    ]);
    TaxCalendar::create([
        'tax_type' => TaxCalendar::TAX_WHT,
        'description' => 'WHT Jan',
        'fiscal_year' => 2026,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'due_date' => '2026-02-15',
        'computed_amount' => 1000000,
        'paid_amount' => 0,
        'penalty_amount' => 50000,
        'filing_status' => TaxCalendar::STATUS_OVERDUE,
    ]);

    $summary = $this->rcService->getTaxSummary(2026);

    expect($summary['total_computed'])->toBe(4000000.0)
        ->and($summary['total_paid'])->toBe(3000000.0)
        ->and($summary['total_penalty'])->toBe(50000.0)
        ->and($summary['total_balance'])->toBe(1050000.0)
        ->and($summary['overdue_count'])->toBe(1)
        ->and($summary['by_type'])->toHaveCount(2);
});
