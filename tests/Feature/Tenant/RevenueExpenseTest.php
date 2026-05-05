<?php

use App\Models\Tenant\Budget;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\ExpenseClaim;
use App\Models\Tenant\Investment;
use App\Models\Tenant\RevenueSource;
use App\Services\Tenant\RevenueExpenseService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->reService = new RevenueExpenseService;
});

// ─── Helpers ────────────────────────────────────────────────────

function createRevenueGlAccount(): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => '4010'],
        [
            'account_name' => 'Interest Income',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'is_header' => false,
            'is_active' => true,
            'level' => 4,
        ]
    );
}

function createExpenseGlAccount(): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => '5010'],
        [
            'account_name' => 'Office Expenses',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'is_header' => false,
            'is_active' => true,
            'level' => 4,
        ]
    );
}

function createAssetGlAccount(): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => '1200'],
        [
            'account_name' => 'Investments',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_header' => false,
            'is_active' => true,
            'level' => 4,
        ]
    );
}

function createLiabilityGlAccount(): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => '2100'],
        [
            'account_name' => 'WHT Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_header' => false,
            'is_active' => true,
            'level' => 4,
        ]
    );
}

// ─── FR-RE-010–014: Revenue Sources & WHT ────────────────────

it('computes WHT on a revenue source correctly', function () {
    $revenueGl = createRevenueGlAccount();
    $whtGl = createLiabilityGlAccount();

    $source = RevenueSource::create([
        'source_code' => 'INT-LOAN',
        'source_name' => 'Loan Interest',
        'revenue_type' => 'interest',
        'recognition_basis' => 'accrual',
        'gl_account_id' => $revenueGl->id,
        'wht_account_id' => $whtGl->id,
        'wht_rate' => 15.00,
        'wht_applicable' => true,
        'is_active' => true,
    ]);

    expect($source->computeWht(100000))->toBe(15000.0)
        ->and($source->computeNetAmount(100000))->toBe(85000.0);
});

it('returns zero WHT when not applicable', function () {
    $revenueGl = createRevenueGlAccount();

    $source = RevenueSource::create([
        'source_code' => 'FEE-PROC',
        'source_name' => 'Processing Fee',
        'revenue_type' => 'fee',
        'recognition_basis' => 'cash',
        'gl_account_id' => $revenueGl->id,
        'wht_applicable' => false,
        'wht_rate' => 0,
        'is_active' => true,
    ]);

    expect($source->computeWht(500000))->toBe(0.0)
        ->and($source->computeNetAmount(500000))->toBe(500000.0);
});

it('recognises revenue through the service with WHT breakdown', function () {
    $revenueGl = createRevenueGlAccount();
    $whtGl = createLiabilityGlAccount();

    RevenueSource::create([
        'source_code' => 'INT-SAVINGS',
        'source_name' => 'Savings Interest',
        'revenue_type' => 'interest',
        'recognition_basis' => 'accrual',
        'gl_account_id' => $revenueGl->id,
        'wht_account_id' => $whtGl->id,
        'wht_rate' => 10.00,
        'wht_applicable' => true,
        'is_active' => true,
    ]);

    $result = $this->reService->recogniseRevenue('INT-SAVINGS', 200000);

    expect($result['gross'])->toBe(200000.0)
        ->and($result['wht'])->toBe(20000.0)
        ->and($result['net'])->toBe(180000.0)
        ->and($result['source'])->toBeInstanceOf(RevenueSource::class);
});

// ─── FR-RE-020–024: Budget Management ────────────────────────

it('computes budget variance correctly', function () {
    $gl = createExpenseGlAccount();

    $budget = Budget::create([
        'budget_code' => 'BUD-2026-001',
        'budget_name' => 'Office Expenses 2026',
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'original_amount' => 1000000,
        'revised_amount' => 1000000,
        'approved_amount' => 1000000,
        'actual_amount' => 400000,
        'status' => Budget::STATUS_ACTIVE,
        'variance_threshold_pct' => 10,
    ]);

    expect($budget->variance)->toBe(600000.0)
        ->and($budget->variance_percentage)->toBe(40.0)
        ->and($budget->remaining)->toBe(600000.0)
        ->and($budget->isOverThreshold())->toBeFalse();
});

it('detects when budget is over threshold', function () {
    $gl = createExpenseGlAccount();

    $budget = Budget::create([
        'budget_code' => 'BUD-OVER',
        'budget_name' => 'Over-spent Budget',
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'original_amount' => 500000,
        'approved_amount' => 500000,
        'actual_amount' => 600000,
        'status' => Budget::STATUS_ACTIVE,
        'variance_threshold_pct' => 10,
    ]);

    expect($budget->isOverThreshold())->toBeTrue()
        ->and($budget->variance_percentage)->toBe(120.0)
        ->and($budget->remaining)->toBe(0.0);
});

it('checks budget availability and blocks over-budget when enforced', function () {
    $gl = createExpenseGlAccount();

    $budget = Budget::create([
        'budget_code' => 'BUD-ENFORCE',
        'budget_name' => 'Enforced Budget',
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'approved_amount' => 100000,
        'actual_amount' => 80000,
        'status' => Budget::STATUS_ACTIVE,
        'enforce_budget' => true,
    ]);

    // Within budget
    $result = $this->reService->checkBudgetAvailability($budget->id, 15000);
    expect($result['allowed'])->toBeTrue()
        ->and($result['remaining'])->toBe(20000.0);

    // Over budget
    $result = $this->reService->checkBudgetAvailability($budget->id, 25000);
    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toContain('exceed budget');
});

it('records actual spend against budget', function () {
    $gl = createExpenseGlAccount();

    $budget = Budget::create([
        'budget_code' => 'BUD-REC',
        'budget_name' => 'Record Test',
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'approved_amount' => 500000,
        'actual_amount' => 0,
        'status' => Budget::STATUS_ACTIVE,
    ]);

    $budget->recordActual(150000);
    $budget->recordActual(50000);

    expect((float) $budget->fresh()->actual_amount)->toBe(200000.0);
});

it('generates budget variance report for a fiscal year', function () {
    $gl = createExpenseGlAccount();

    Budget::create([
        'budget_code' => 'BUD-RPT-1',
        'budget_name' => 'Report Budget 1',
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'approved_amount' => 1000000,
        'actual_amount' => 750000,
        'status' => Budget::STATUS_ACTIVE,
    ]);

    $report = $this->reService->getBudgetVarianceReport(2026);

    expect($report)->toHaveCount(1);
    $row = $report->first();
    expect($row['approved'])->toBe(1000000.0)
        ->and($row['actual'])->toBe(750000.0)
        ->and($row['variance'])->toBe(250000.0)
        ->and($row['variance_pct'])->toBe(75.0);
});

// ─── FR-RE-020–024: Expense Claims ──────────────────────────

it('generates unique claim numbers', function () {
    $num1 = $this->reService->generateClaimNumber();
    $num2 = $this->reService->generateClaimNumber();

    expect($num1)->toStartWith('EXP-'.now()->format('Y').'-')
        ->and($num1)->toBe($num2); // both are 0001 since none exist yet

    // Create one, then the next should increment
    $gl = createExpenseGlAccount();
    ExpenseClaim::create([
        'claim_number' => $num1,
        'claimant_name' => 'John',
        'category' => 'travel',
        'gl_account_id' => $gl->id,
        'claimed_amount' => 100000,
        'description' => 'Test',
        'expense_date' => now(),
        'status' => 'draft',
    ]);

    $num3 = $this->reService->generateClaimNumber();
    expect($num3)->not->toBe($num1);
});

it('processes expense claim approval workflow', function () {
    $gl = createExpenseGlAccount();

    $claim = ExpenseClaim::create([
        'claim_number' => 'EXP-2026-0001',
        'claimant_name' => 'Alice',
        'category' => 'training',
        'gl_account_id' => $gl->id,
        'claimed_amount' => 500000,
        'description' => 'Training course',
        'expense_date' => '2026-03-01',
        'status' => ExpenseClaim::STATUS_DRAFT,
    ]);

    // Submit
    $claim->submit();
    expect($claim->fresh()->status)->toBe(ExpenseClaim::STATUS_SUBMITTED);

    // Approve with partial amount
    $claim->approve(approverId: 1, approvedAmount: 400000);
    expect($claim->fresh()->status)->toBe(ExpenseClaim::STATUS_APPROVED)
        ->and((float) $claim->fresh()->approved_amount)->toBe(400000.0)
        ->and($claim->fresh()->approved_by)->toBe(1);

    // Mark paid
    $claim->markPaid();
    expect($claim->fresh()->status)->toBe(ExpenseClaim::STATUS_PAID)
        ->and($claim->fresh()->paid_at)->not->toBeNull();
});

it('rejects an expense claim with reason', function () {
    $gl = createExpenseGlAccount();

    $claim = ExpenseClaim::create([
        'claim_number' => 'EXP-2026-0002',
        'claimant_name' => 'Bob',
        'category' => 'supplies',
        'gl_account_id' => $gl->id,
        'claimed_amount' => 800000,
        'description' => 'Office furniture',
        'expense_date' => '2026-03-05',
        'status' => ExpenseClaim::STATUS_SUBMITTED,
    ]);

    $claim->reject(reviewerId: 2, reason: 'Exceeds quarterly limit');

    expect($claim->fresh()->status)->toBe(ExpenseClaim::STATUS_REJECTED)
        ->and($claim->fresh()->rejection_reason)->toBe('Exceeds quarterly limit');
});

it('blocks marking unpproved claim as paid', function () {
    $gl = createExpenseGlAccount();

    $claim = ExpenseClaim::create([
        'claim_number' => 'EXP-2026-0003',
        'claimant_name' => 'Charlie',
        'category' => 'utilities',
        'gl_account_id' => $gl->id,
        'claimed_amount' => 200000,
        'description' => 'Electric bill',
        'expense_date' => '2026-03-10',
        'status' => ExpenseClaim::STATUS_SUBMITTED,
    ]);

    $claim->markPaid();
})->throws(RuntimeException::class, 'not approved');

it('records actual against budget when expense claim is approved', function () {
    $gl = createExpenseGlAccount();

    $budget = Budget::create([
        'budget_code' => 'BUD-CLAIM',
        'budget_name' => 'Expense Budget',
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'approved_amount' => 500000,
        'actual_amount' => 0,
        'status' => Budget::STATUS_ACTIVE,
    ]);

    $claim = ExpenseClaim::create([
        'claim_number' => 'EXP-2026-0004',
        'claimant_name' => 'Diana',
        'category' => 'travel',
        'gl_account_id' => $gl->id,
        'claimed_amount' => 150000,
        'description' => 'Trip to field office',
        'expense_date' => '2026-03-12',
        'status' => ExpenseClaim::STATUS_SUBMITTED,
        'budget_id' => $budget->id,
    ]);

    $claim->approve(approverId: 1);

    expect((float) $budget->fresh()->actual_amount)->toBe(150000.0);
});

// ─── FR-RE-030–034: Investment Portfolio ─────────────────────

it('computes investment ROI correctly', function () {
    $gl = createAssetGlAccount();

    $investment = Investment::create([
        'investment_code' => 'INV-001',
        'name' => 'Treasury Bill 91-day',
        'investment_type' => 'treasury_bill',
        'gl_account_id' => $gl->id,
        'face_value' => 10000000,
        'purchase_price' => 9800000,
        'current_value' => 10000000,
        'interest_rate' => 8.5,
        'purchase_date' => '2026-01-15',
        'maturity_date' => '2026-04-15',
        'status' => Investment::STATUS_ACTIVE,
    ]);

    // ROI = (10M - 9.8M) / 9.8M * 100 = 2.04%
    expect($investment->roi)->toBe(2.04)
        ->and($investment->unrealised_gain_loss)->toBe(200000.0);
});

it('tracks investment maturity status', function () {
    $gl = createAssetGlAccount();

    $investment = Investment::create([
        'investment_code' => 'INV-MAT',
        'name' => 'Matured FD',
        'investment_type' => 'fixed_deposit',
        'gl_account_id' => $gl->id,
        'face_value' => 5000000,
        'purchase_price' => 5000000,
        'current_value' => 5250000,
        'purchase_date' => '2025-01-01',
        'maturity_date' => '2026-01-01',
        'status' => Investment::STATUS_ACTIVE,
    ]);

    expect($investment->isMatured())->toBeTrue()
        ->and($investment->days_to_maturity)->toBe(0);
});

it('revalues an investment (mark-to-market)', function () {
    $gl = createAssetGlAccount();

    $investment = Investment::create([
        'investment_code' => 'INV-REVAL',
        'name' => 'Equity Holding',
        'investment_type' => 'equity',
        'gl_account_id' => $gl->id,
        'face_value' => 2000000,
        'purchase_price' => 2000000,
        'current_value' => 2000000,
        'purchase_date' => '2026-02-01',
        'status' => Investment::STATUS_ACTIVE,
    ]);

    $investment->revalue(2300000);

    expect((float) $investment->fresh()->current_value)->toBe(2300000.0)
        ->and($investment->fresh()->last_valuation_date)->not->toBeNull();
});

it('provides portfolio summary with aggregated metrics', function () {
    $gl = createAssetGlAccount();
    $incomeGl = createRevenueGlAccount();

    Investment::create([
        'investment_code' => 'INV-P1',
        'name' => 'T-Bill A',
        'investment_type' => 'treasury_bill',
        'gl_account_id' => $gl->id,
        'income_account_id' => $incomeGl->id,
        'face_value' => 10000000,
        'purchase_price' => 9700000,
        'current_value' => 10000000,
        'accrued_income' => 100000,
        'purchase_date' => '2026-01-01',
        'status' => Investment::STATUS_ACTIVE,
    ]);

    Investment::create([
        'investment_code' => 'INV-P2',
        'name' => 'FD B',
        'investment_type' => 'fixed_deposit',
        'gl_account_id' => $gl->id,
        'face_value' => 5000000,
        'purchase_price' => 5000000,
        'current_value' => 5200000,
        'accrued_income' => 50000,
        'purchase_date' => '2026-02-01',
        'status' => Investment::STATUS_ACTIVE,
    ]);

    $summary = $this->reService->getPortfolioSummary();

    expect($summary['total_invested'])->toBe(14700000.0)
        ->and($summary['total_current_value'])->toBe(15200000.0)
        ->and($summary['total_accrued_income'])->toBe(150000.0)
        ->and($summary['unrealised_gain_loss'])->toBe(500000.0)
        ->and($summary['portfolio_roi'])->toBeGreaterThan(0)
        ->and($summary['by_type'])->toHaveCount(2);
});
