<?php

use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\JournalEntry;
use App\Services\Tenant\GeneralLedgerService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->glService = new GeneralLedgerService;
});

// ─── Helpers ────────────────────────────────────────────────────

function createGlAccount(string $code, string $name, string $type, string $normalBalance = 'debit', bool $isHeader = false): ChartOfAccount
{
    return ChartOfAccount::firstOrCreate(
        ['account_code' => $code],
        [
            'account_name' => $name,
            'account_type' => $type,
            'normal_balance' => $normalBalance,
            'is_header' => $isHeader,
            'is_active' => true,
            'level' => $isHeader ? 1 : 4,
        ]
    );
}

function createOpenPeriod(int $year = 2026, int $month = 3): AccountingPeriod
{
    return AccountingPeriod::firstOrCreate(
        ['year' => $year, 'month' => $month],
        [
            'period_name' => "Period {$month}/{$year}",
            'start_date' => "{$year}-".str_pad($month, 2, '0', STR_PAD_LEFT).'-01',
            'end_date' => "{$year}-".str_pad($month, 2, '0', STR_PAD_LEFT).'-28',
            'status' => AccountingPeriod::STATUS_OPEN,
        ]
    );
}

// ─── FR-GL-001: Double-entry enforcement ──────────────────────

it('creates a balanced journal entry with two lines', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset', 'debit');
    $revenue = createGlAccount('4010', 'Interest Income', 'revenue', 'credit');

    $entry = $this->glService->createJournalEntry(
        description: 'Interest received',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 50000],
        ],
    );

    expect($entry)->toBeInstanceOf(JournalEntry::class)
        ->and($entry->isBalanced())->toBeTrue()
        ->and((float) $entry->total_debit)->toBe(50000.0)
        ->and((float) $entry->total_credit)->toBe(50000.0)
        ->and($entry->lines)->toHaveCount(2)
        ->and($entry->status)->toBe(JournalEntry::STATUS_POSTED);
});

it('rejects an unbalanced journal entry', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');

    $this->glService->createJournalEntry(
        description: 'Bad entry',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 40000],
        ],
    );
})->throws(RuntimeException::class, 'unbalanced');

it('rejects a journal entry with fewer than two lines', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');

    $this->glService->createJournalEntry(
        description: 'Single line',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
        ],
    );
})->throws(RuntimeException::class, 'at least two lines');

it('rejects posting to a header account', function () {
    createOpenPeriod();
    $header = createGlAccount('1000', 'Assets', 'asset', 'debit', true);
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');

    $this->glService->createJournalEntry(
        description: 'Post to header',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $header->id, 'debit' => 10000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 10000],
        ],
    );
})->throws(RuntimeException::class, 'headers');

// ─── FR-GL-002: Journal types ─────────────────────────────────

it('creates manual journal entry as draft', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $expense = createGlAccount('5010', 'Office Rent', 'expense');

    $entry = $this->glService->createJournalEntry(
        description: 'Monthly rent',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $expense->id, 'debit' => 20000, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 20000],
        ],
        journalType: JournalEntry::TYPE_MANUAL,
        createdBy: 1,
    );

    expect($entry->status)->toBe(JournalEntry::STATUS_DRAFT)
        ->and($entry->posted_at)->toBeNull();
});

it('approves a manual journal with four-eyes principle', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $expense = createGlAccount('5010', 'Expense', 'expense');

    $entry = $this->glService->createJournalEntry(
        description: 'Manual entry',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $expense->id, 'debit' => 10000, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 10000],
        ],
        journalType: JournalEntry::TYPE_MANUAL,
        createdBy: 1,
    );

    $this->glService->approveManualJournal($entry, 2);

    expect($entry->fresh()->status)->toBe(JournalEntry::STATUS_POSTED)
        ->and($entry->fresh()->approved_by)->toBe(2);
});

it('blocks self-approval of manual journal', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $expense = createGlAccount('5010', 'Expense', 'expense');

    $entry = $this->glService->createJournalEntry(
        description: 'Self-approve attempt',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $expense->id, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 5000],
        ],
        journalType: JournalEntry::TYPE_MANUAL,
        createdBy: 1,
    );

    $this->glService->approveManualJournal($entry, 1);
})->throws(RuntimeException::class, 'cannot approve their own');

// ─── FR-GL-002: Reversal ──────────────────────────────────────

it('reverses a posted journal by creating a mirror entry', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');

    $original = $this->glService->createJournalEntry(
        description: 'Original entry',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $cash->id, 'debit' => 25000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 25000],
        ],
    );

    $reversal = $this->glService->reverseJournalEntry($original, 1, 'Error correction');

    expect($original->fresh()->status)->toBe(JournalEntry::STATUS_REVERSED)
        ->and($reversal->is_reversal)->toBeTrue()
        ->and($reversal->reversal_of_id)->toBe($original->id)
        ->and($reversal->isBalanced())->toBeTrue();

    // Verify the mirror: original cash debit became reversal cash credit
    $reversalCashLine = $reversal->lines()->where('account_id', $cash->id)->first();
    expect((float) $reversalCashLine->credit)->toBe(25000.0)
        ->and((float) $reversalCashLine->debit)->toBe(0.0);
});

// ─── FR-GL-003: Period controls ───────────────────────────────

it('blocks posting to a closed period', function () {
    $period = createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');

    $period->closePeriod(1, 'Month end');

    $this->glService->createJournalEntry(
        description: 'Post to closed period',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $cash->id, 'debit' => 10000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 10000],
        ],
    );
})->throws(RuntimeException::class, 'closed');

it('allows posting after period reopen', function () {
    $period = createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset');
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');

    $period->closePeriod(1, 'Month end');
    $period->reopenPeriod(2);

    $entry = $this->glService->createJournalEntry(
        description: 'After reopen',
        transactionDate: '2026-03-15',
        lines: [
            ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 5000],
        ],
    );

    expect($entry->status)->toBe(JournalEntry::STATUS_POSTED);
});

it('records close and reopen audit trail on periods', function () {
    $period = createOpenPeriod();

    $period->closePeriod(1, 'EOM close');

    expect($period->fresh()->status)->toBe(AccountingPeriod::STATUS_CLOSED)
        ->and($period->fresh()->closed_by)->toBe(1)
        ->and($period->fresh()->closed_at)->not->toBeNull();

    $period->reopenPeriod(2);

    expect($period->fresh()->status)->toBe(AccountingPeriod::STATUS_OPEN)
        ->and($period->fresh()->reopened_by)->toBe(2);
});

// ─── FR-GL-004: Trial balance ─────────────────────────────────

it('computes trial balance with correct totals', function () {
    $period = createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset', 'debit');
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');
    $expense = createGlAccount('5010', 'Expense', 'expense', 'debit');

    // Entry 1: receive cash 50,000
    $this->glService->createJournalEntry(
        description: 'Cash receipt',
        transactionDate: '2026-03-10',
        lines: [
            ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 50000],
        ],
    );

    // Entry 2: pay expense 15,000
    $this->glService->createJournalEntry(
        description: 'Expense payment',
        transactionDate: '2026-03-12',
        lines: [
            ['account_id' => $expense->id, 'debit' => 15000, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 15000],
        ],
    );

    $tb = $this->glService->computeTrialBalance(periodId: $period->id);

    expect($tb)->toHaveCount(3);

    // Cash: debit 50,000 - credit 15,000 = balance 35,000 (debit normal)
    $cashRow = $tb->firstWhere('account_code', '1010');
    expect($cashRow['balance'])->toBe(35000.0);

    // Revenue: credit 50,000 - debit 0 = balance 50,000 (credit normal)
    $revenueRow = $tb->firstWhere('account_code', '4010');
    expect($revenueRow['balance'])->toBe(50000.0);

    // Expense: debit 15,000 = balance 15,000 (debit normal)
    $expenseRow = $tb->firstWhere('account_code', '5010');
    expect($expenseRow['balance'])->toBe(15000.0);

    // Total debits (across all accounts) should equal total credits
    $tbTotalDebit = $tb->sum(fn ($r) => $r['total_debit']);
    $tbTotalCredit = $tb->sum(fn ($r) => $r['total_credit']);
    expect(abs($tbTotalDebit - $tbTotalCredit))->toBeLessThanOrEqual(0.01);
});

it('computes account balance correctly', function () {
    createOpenPeriod();
    $cash = createGlAccount('1010', 'Cash', 'asset', 'debit');
    $revenue = createGlAccount('4010', 'Revenue', 'revenue', 'credit');

    $this->glService->createJournalEntry(
        description: 'Deposit',
        transactionDate: '2026-03-05',
        lines: [
            ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100000],
        ],
    );

    $this->glService->createJournalEntry(
        description: 'Withdrawal',
        transactionDate: '2026-03-10',
        lines: [
            ['account_id' => $revenue->id, 'debit' => 30000, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 30000],
        ],
    );

    // Cash: 100,000 debit - 30,000 credit = 70,000 (debit normal)
    expect($this->glService->getAccountBalance($cash->id))->toBe(70000.0);

    // As of 2026-03-07 — only the first entry counts
    expect($this->glService->getAccountBalance($cash->id, '2026-03-07'))->toBe(100000.0);
});
