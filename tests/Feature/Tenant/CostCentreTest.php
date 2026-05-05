<?php

use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\CostAllocation;
use App\Models\Tenant\CostCentre;
use App\Services\Tenant\CostCentreService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->ccService = new CostCentreService;
});

// ─── Helpers ────────────────────────────────────────────────────

function createCcRevenueAccount(): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => '4020'],
        [
            'account_name' => 'Service Revenue',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'is_header' => false,
            'is_active' => true,
            'level' => 4,
        ],
    );
}

function createCcExpenseAccount(): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => '5020'],
        [
            'account_name' => 'Admin Expenses',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'is_header' => false,
            'is_active' => true,
            'level' => 4,
        ],
    );
}

function createDivision(string $code = 'DIV-FIN', string $name = 'Finance Division'): CostCentre
{
    return CostCentre::create([
        'code' => $code,
        'name' => $name,
        'level' => CostCentre::LEVEL_DIVISION,
        'is_active' => true,
    ]);
}

function createDepartment(CostCentre $parent, string $code = 'DEPT-ACC', string $name = 'Accounting'): CostCentre
{
    return CostCentre::create([
        'code' => $code,
        'name' => $name,
        'level' => CostCentre::LEVEL_DEPARTMENT,
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);
}

// ─── FR-CC-001: 4-level hierarchy ────────────────────────────

it('creates a 4-level cost centre hierarchy', function () {
    $division = $this->ccService->createCostCentre([
        'code' => 'DIV-OPS',
        'name' => 'Operations',
        'level' => CostCentre::LEVEL_DIVISION,
    ]);

    $dept = $this->ccService->createCostCentre([
        'code' => 'DEPT-LOAN',
        'name' => 'Loan Department',
        'level' => CostCentre::LEVEL_DEPARTMENT,
        'parent_id' => $division->id,
    ]);

    $branch = $this->ccService->createCostCentre([
        'code' => 'BR-HQ',
        'name' => 'HQ Branch',
        'level' => CostCentre::LEVEL_BRANCH,
        'parent_id' => $dept->id,
    ]);

    $unit = $this->ccService->createCostCentre([
        'code' => 'UT-PROC',
        'name' => 'Processing Unit',
        'level' => CostCentre::LEVEL_UNIT,
        'parent_id' => $branch->id,
    ]);

    expect($division->level)->toBe(1)
        ->and($dept->level)->toBe(2)
        ->and($branch->level)->toBe(3)
        ->and($unit->level)->toBe(4)
        ->and($unit->parent->id)->toBe($branch->id)
        ->and($branch->parent->id)->toBe($dept->id);
});

it('rejects root cost centre that is not division level', function () {
    $this->ccService->createCostCentre([
        'code' => 'BAD-ROOT',
        'name' => 'Wrong Root',
        'level' => CostCentre::LEVEL_BRANCH,
    ]);
})->throws(RuntimeException::class, 'Division level');

it('rejects child at wrong level relative to parent', function () {
    $division = createDivision();

    $this->ccService->createCostCentre([
        'code' => 'SKIP-LEVEL',
        'name' => 'Skipped Level',
        'level' => CostCentre::LEVEL_BRANCH,  // Should be Department (2), not Branch (3)
        'parent_id' => $division->id,
    ]);
})->throws(RuntimeException::class, 'one below parent');

it('computes the full hierarchy path', function () {
    $division = createDivision();
    $dept = createDepartment($division);

    expect($dept->path)->toBe('Finance Division > Accounting');
});

it('retrieves a hierarchy tree from the service', function () {
    $div1 = createDivision('DIV-A', 'Division A');
    $div2 = createDivision('DIV-B', 'Division B');
    createDepartment($div1, 'DEPT-A1', 'Dept A1');
    createDepartment($div1, 'DEPT-A2', 'Dept A2');

    $tree = $this->ccService->getHierarchyTree();

    expect($tree)->toHaveCount(2)
        ->and($tree->first()->children)->toHaveCount(2);
});

it('collects all subtree IDs', function () {
    $div = createDivision();
    $dept = createDepartment($div);
    $branch = CostCentre::create([
        'code' => 'BR-1',
        'name' => 'Branch 1',
        'level' => CostCentre::LEVEL_BRANCH,
        'parent_id' => $dept->id,
        'is_active' => true,
    ]);

    $ids = $div->load('children.children')->getSubtreeIds();

    expect($ids)->toContain($div->id)
        ->and($ids)->toContain($dept->id)
        ->and($ids)->toContain($branch->id)
        ->and($ids)->toHaveCount(3);
});

// ─── FR-CC-002: Historical data preservation ────────────────

it('deactivates a cost centre with audit trail', function () {
    $division = createDivision();

    $this->ccService->deactivate($division, userId: 1, reason: 'Restructuring');

    $division->refresh();
    expect($division->is_active)->toBeFalse()
        ->and($division->deactivated_by)->toBe(1)
        ->and($division->deactivation_reason)->toBe('Restructuring')
        ->and($division->deactivated_at)->not->toBeNull();
});

it('reactivates a deactivated cost centre', function () {
    $division = createDivision();
    $division->deactivate(1, 'Test');

    $division->reactivate();

    expect($division->fresh()->is_active)->toBeTrue()
        ->and($division->fresh()->deactivated_at)->toBeNull();
});

it('cascades deactivation to children', function () {
    $division = createDivision();
    $dept = createDepartment($division);

    $this->ccService->deactivate($division, userId: 1, reason: 'Full shutdown', cascadeToChildren: true);

    expect($division->fresh()->is_active)->toBeFalse()
        ->and($dept->fresh()->is_active)->toBeFalse();
});

it('blocks deactivation when active allocations exist (without cascade)', function () {
    $division = createDivision();
    $gl = createCcExpenseAccount();

    CostAllocation::create([
        'cost_centre_id' => $division->id,
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'allocated_amount' => 100000,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);

    $this->ccService->deactivate($division, userId: 1, reason: 'Test');
})->throws(RuntimeException::class, 'active allocation');

// ─── FR-CC-003: Internal charge-backs ───────────────────────

it('creates an internal charge-back between two cost centres', function () {
    $from = createDivision('DIV-IT', 'IT Division');
    $to = createDivision('DIV-FIN', 'Finance Division');
    $gl = createCcExpenseAccount();

    $allocation = $this->ccService->createChargeback(
        fromCostCentreId: $from->id,
        toCostCentreId: $to->id,
        glAccountId: $gl->id,
        amount: 250000,
        fiscalYear: 2026,
        periodMonth: 3,
        description: 'IT support services March',
    );

    expect($allocation)->toBeInstanceOf(CostAllocation::class)
        ->and($allocation->isChargeback())->toBeTrue()
        ->and($allocation->chargeback_from_id)->toBe($from->id)
        ->and($allocation->cost_centre_id)->toBe($to->id)
        ->and((float) $allocation->transfer_price)->toBe(250000.0)
        ->and($allocation->chargeback_description)->toContain('IT support');
});

it('blocks charge-back to the same cost centre', function () {
    $cc = createDivision();
    $gl = createCcExpenseAccount();

    $this->ccService->createChargeback(
        fromCostCentreId: $cc->id,
        toCostCentreId: $cc->id,
        glAccountId: $gl->id,
        amount: 100000,
        fiscalYear: 2026,
    );
})->throws(RuntimeException::class, 'same cost centre');

it('blocks charge-back involving inactive cost centres', function () {
    $from = createDivision('DIV-A', 'A');
    $to = createDivision('DIV-B', 'B');
    $to->deactivate(1, 'Closed');
    $gl = createCcExpenseAccount();

    $this->ccService->createChargeback(
        fromCostCentreId: $from->id,
        toCostCentreId: $to->id,
        glAccountId: $gl->id,
        amount: 50000,
        fiscalYear: 2026,
    );
})->throws(RuntimeException::class, 'must be active');

// ─── FR-CC-004: Cost Centre P&L ─────────────────────────────

it('generates a cost centre P&L report', function () {
    $division = createDivision();
    $revenueGl = createCcRevenueAccount();
    $expenseGl = createCcExpenseAccount();

    CostAllocation::create([
        'cost_centre_id' => $division->id,
        'gl_account_id' => $revenueGl->id,
        'fiscal_year' => 2026,
        'allocated_amount' => 1000000,
        'actual_amount' => 900000,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);

    CostAllocation::create([
        'cost_centre_id' => $division->id,
        'gl_account_id' => $expenseGl->id,
        'fiscal_year' => 2026,
        'allocated_amount' => 500000,
        'actual_amount' => 450000,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);

    $pnl = $this->ccService->getCostCentrePnL($division->id, 2026);

    expect($pnl['total_revenue'])->toBe(900000.0)
        ->and($pnl['total_expense'])->toBe(450000.0)
        ->and($pnl['net_income'])->toBe(450000.0)
        ->and($pnl['revenues'])->toHaveCount(1)
        ->and($pnl['expenses'])->toHaveCount(1);
});

it('generates a consolidated P&L across all cost centres', function () {
    $div1 = createDivision('DIV-1', 'Division 1');
    $div2 = createDivision('DIV-2', 'Division 2');
    $revenueGl = createCcRevenueAccount();
    $expenseGl = createCcExpenseAccount();

    CostAllocation::create([
        'cost_centre_id' => $div1->id,
        'gl_account_id' => $revenueGl->id,
        'fiscal_year' => 2026,
        'actual_amount' => 600000,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);
    CostAllocation::create([
        'cost_centre_id' => $div1->id,
        'gl_account_id' => $expenseGl->id,
        'fiscal_year' => 2026,
        'actual_amount' => 200000,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);
    CostAllocation::create([
        'cost_centre_id' => $div2->id,
        'gl_account_id' => $revenueGl->id,
        'fiscal_year' => 2026,
        'actual_amount' => 400000,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);

    $report = $this->ccService->getConsolidatedPnL(2026);

    expect($report)->toHaveCount(2);

    $row1 = $report->firstWhere('code', 'DIV-1');
    expect($row1['net_income'])->toBe(400000.0);

    $row2 = $report->firstWhere('code', 'DIV-2');
    expect($row2['total_revenue'])->toBe(400000.0)
        ->and($row2['total_expense'])->toBe(0.0)
        ->and($row2['net_income'])->toBe(400000.0);
});

// ─── Allocation features ────────────────────────────────────

it('computes allocation from total using percentage', function () {
    $division = createDivision();
    $gl = createCcExpenseAccount();

    $allocation = CostAllocation::create([
        'cost_centre_id' => $division->id,
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'allocation_method' => CostAllocation::METHOD_PROPORTIONAL,
        'allocation_percentage' => 35.50,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);

    expect($allocation->computeAllocation(1000000))->toBe(355000.0);
});

it('records actual amount incrementally', function () {
    $division = createDivision();
    $gl = createCcExpenseAccount();

    $allocation = CostAllocation::create([
        'cost_centre_id' => $division->id,
        'gl_account_id' => $gl->id,
        'fiscal_year' => 2026,
        'allocated_amount' => 500000,
        'actual_amount' => 0,
        'status' => CostAllocation::STATUS_ACTIVE,
    ]);

    $allocation->recordActual(100000);
    $allocation->recordActual(75000);

    expect((float) $allocation->fresh()->actual_amount)->toBe(175000.0)
        ->and($allocation->fresh()->variance)->toBe(325000.0);
});
