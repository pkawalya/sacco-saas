<?php

use App\Models\Tenant\FixedDeposit;
use App\Models\Tenant\Member;
use App\Models\Tenant\SavingsAccount;
use App\Models\Tenant\SavingsProduct;
use App\Models\Tenant\SavingsTransaction;
use App\Services\Tenant\SavingsService;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->initializeTenancy();
});

// ─── Helpers ──────────────────────────────────────────────────

function makeProduct(array $overrides = []): SavingsProduct
{
    return SavingsProduct::create(array_merge([
        'product_code' => 'SAV-'.fake()->unique()->word(),
        'product_name' => 'Regular Savings',
        'product_type' => SavingsProduct::TYPE_REGULAR,
        'interest_rate' => 6.0000,
        'interest_computation' => SavingsProduct::COMPUTATION_DAILY_AVERAGE,
        'interest_posting_cycle' => 'monthly',
        'minimum_balance' => 5000.00,
        'minimum_opening_deposit' => 10000.00,
        'is_active' => true,
    ], $overrides));
}

function makeAccount(SavingsProduct $product, Member $member, array $overrides = []): SavingsAccount
{
    return SavingsAccount::create(array_merge([
        'account_number' => 'ACC-'.fake()->unique()->numerify('######'),
        'member_id' => $member->id,
        'product_id' => $product->id,
        'ledger_balance' => 100000.00,
        'available_balance' => 100000.00,
        'held_amount' => 0.00,
        'accrued_interest' => 0.00,
        'status' => SavingsAccount::STATUS_ACTIVE,
        'opened_date' => now(),
    ], $overrides));
}

// ─── FR-SD-001: Product CRUD ──────────────────────────────────

it('can create a savings product with all required fields', function () {
    $product = makeProduct(['product_name' => 'Test Savings']);

    expect($product)->toBeInstanceOf(SavingsProduct::class)
        ->and($product->product_name)->toBe('Test Savings')
        ->and($product->is_active)->toBeTrue()
        ->and($product->interest_rate)->toBe('6.0000');
});

it('product code must be unique', function () {
    makeProduct(['product_code' => 'SAV-DUP']);

    expect(fn () => makeProduct(['product_code' => 'SAV-DUP']))
        ->toThrow(QueryException::class);
});

// ─── FR-SD-003: Tiered interest rates ────────────────────────

it('returns base rate when no tiers are configured', function () {
    $product = makeProduct(['interest_rate' => 5.0000, 'has_tiered_rates' => false]);

    expect($product->getApplicableRate(50000))->toBe(5.0);
});

it('returns correct tiered rate based on balance', function () {
    $product = makeProduct([
        'has_tiered_rates' => true,
        'tier_rates' => [
            ['min_balance' => 0, 'max_balance' => 99999, 'rate' => 4.0],
            ['min_balance' => 100000, 'max_balance' => 999999, 'rate' => 6.0],
            ['min_balance' => 1000000, 'rate' => 8.0],
        ],
    ]);

    expect($product->getApplicableRate(50000))->toBe(4.0)
        ->and($product->getApplicableRate(500000))->toBe(6.0)
        ->and($product->getApplicableRate(2000000))->toBe(8.0);
});

// ─── FR-SD-012: Deposit processing ──────────────────────────

it('can deposit funds into a savings account', function () {
    $product = makeProduct();
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 0, 'available_balance' => 0]);

    $service = new SavingsService;
    $txn = $service->deposit($account, 50000, SavingsTransaction::CHANNEL_BRANCH);

    expect($txn)->toBeInstanceOf(SavingsTransaction::class)
        ->and($txn->transaction_type)->toBe(SavingsTransaction::TYPE_DEPOSIT)
        ->and((float) $txn->amount)->toBe(50000.0)
        ->and($account->fresh()->ledger_balance)->toEqual('50000.00')
        ->and($account->fresh()->available_balance)->toEqual('50000.00');
});

it('rejects deposit of zero or negative amount', function () {
    $product = makeProduct();
    $member = Member::factory()->create();
    $account = makeAccount($product, $member);
    $service = new SavingsService;

    expect(fn () => $service->deposit($account, 0))->toThrow(RuntimeException::class)
        ->and(fn () => $service->deposit($account, -100))->toThrow(RuntimeException::class);
});

// ─── FR-SD-002: Minimum balance enforcement ──────────────────

it('can withdraw funds without breaching minimum balance', function () {
    $product = makeProduct(['minimum_balance' => 5000]);
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 100000, 'available_balance' => 100000]);

    $service = new SavingsService;
    $txn = $service->withdraw($account, 90000);

    expect($txn->transaction_type)->toBe(SavingsTransaction::TYPE_WITHDRAWAL)
        ->and((float) $account->fresh()->ledger_balance)->toBe(10000.0);
});

it('blocks withdrawal that would breach minimum balance', function () {
    $product = makeProduct(['minimum_balance' => 5000]);
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 10000, 'available_balance' => 10000]);

    $service = new SavingsService;

    // 10000 - 6000 = 4000 < 5000 minimum → should throw
    expect(fn () => $service->withdraw($account, 6000))
        ->toThrow(RuntimeException::class, 'minimum balance');
});

it('blocks withdrawal when insufficient available balance', function () {
    $product = makeProduct(['minimum_balance' => 0]);
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 10000, 'available_balance' => 3000]);

    $service = new SavingsService;

    expect(fn () => $service->withdraw($account, 5000))
        ->toThrow(RuntimeException::class, 'Insufficient');
});

// ─── FR-SD-015: Transfers ────────────────────────────────────

it('can transfer between two accounts', function () {
    $product = makeProduct(['minimum_balance' => 0]);
    $member = Member::factory()->create();
    $fromAccount = makeAccount($product, $member, ['ledger_balance' => 100000, 'available_balance' => 100000]);
    $toAccount = makeAccount($product, $member, ['account_number' => 'ACC-TO-001', 'ledger_balance' => 0, 'available_balance' => 0]);

    $service = new SavingsService;
    [$debit, $credit] = $service->transfer($fromAccount, $toAccount, 30000);

    expect($debit->transaction_type)->toBe(SavingsTransaction::TYPE_TRANSFER_OUT)
        ->and($credit->transaction_type)->toBe(SavingsTransaction::TYPE_TRANSFER_IN)
        ->and((float) $fromAccount->fresh()->ledger_balance)->toBe(70000.0)
        ->and((float) $toAccount->fresh()->ledger_balance)->toBe(30000.0);
});

// ─── FR-SD-014: Account closure checklist ───────────────────

it('allows closure when account has no balance or holds', function () {
    $product = makeProduct();
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 0, 'available_balance' => 0, 'held_amount' => 0]);

    $service = new SavingsService;
    $blocks = $service->getClosureBlockReasons($account);

    expect($blocks)->toBeEmpty();
});

it('blocks closure when account has remaining balance', function () {
    $product = makeProduct();
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 5000, 'available_balance' => 5000]);

    $service = new SavingsService;
    $blocks = $service->getClosureBlockReasons($account);

    expect($blocks)->not->toBeEmpty()
        ->and($blocks[0])->toContain('remaining balance');
});

it('blocks closure when account has a hold', function () {
    $product = makeProduct();
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 0, 'available_balance' => 0, 'held_amount' => 10000]);

    $service = new SavingsService;
    $blocks = $service->getClosureBlockReasons($account);

    expect($blocks)->not->toBeEmpty()
        ->and($blocks[0])->toContain('hold');
});

// ─── FR-SD-020: Interest computation ────────────────────────

it('computes daily interest for single day correctly', function () {
    $product = makeProduct(['interest_rate' => 12.0000]); // 12% p.a.
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 365000]);

    $service = new SavingsService;

    // 365000 × (12/100) × (1/365) = 120 per day
    $interest = $service->computeInterest($account, now()->startOfDay(), now()->startOfDay()->addDay());

    expect($interest)->toBe(120.0);
});

it('computes interest for 30-day period', function () {
    $product = makeProduct(['interest_rate' => 6.0000]); // 6% p.a.
    $member = Member::factory()->create();
    $account = makeAccount($product, $member, ['ledger_balance' => 1000000]);

    $service = new SavingsService;
    $interest = $service->computeInterest(
        $account,
        now()->startOfMonth(),
        now()->startOfMonth()->addDays(30)
    );

    // 1000000 × 0.06 × (30/365) ≈ 4931.5068
    expect($interest)->toBeGreaterThan(4900)->toBeLessThan(5000);
});

// ─── FR-SD-022: Fixed deposit maturity computation ──────────

it('computes correct maturity amount for fixed deposit', function () {
    $product = makeProduct(['product_type' => SavingsProduct::TYPE_FIXED_DEPOSIT]);
    $member = Member::factory()->create();

    $fd = FixedDeposit::create([
        'fd_number' => 'FD-001',
        'member_id' => $member->id,
        'product_id' => $product->id,
        'principal_amount' => 1000000.00,
        'interest_rate' => 12.0000,
        'tenure_months' => 12,
        'start_date' => now()->subYear(),
        'maturity_date' => now(),
        'status' => FixedDeposit::STATUS_ACTIVE,
    ]);

    // 1,000,000 × 12% × (12/12) = 1,120,000
    $maturity = $fd->computeMaturityAmount();

    expect($maturity)->toBe(1120000.0);
});

it('identifies fixed deposits due for maturity', function () {
    $product = makeProduct(['product_type' => SavingsProduct::TYPE_FIXED_DEPOSIT]);
    $member = Member::factory()->create();

    $maturedFd = FixedDeposit::create([
        'fd_number' => 'FD-MATURED',
        'member_id' => $member->id,
        'product_id' => $product->id,
        'principal_amount' => 500000,
        'interest_rate' => 10.0000,
        'tenure_months' => 6,
        'start_date' => now()->subMonths(7),
        'maturity_date' => now()->subDay(), // past
        'status' => FixedDeposit::STATUS_ACTIVE,
    ]);

    $activeFd = FixedDeposit::create([
        'fd_number' => 'FD-ACTIVE',
        'member_id' => $member->id,
        'product_id' => $product->id,
        'principal_amount' => 500000,
        'interest_rate' => 10.0000,
        'tenure_months' => 6,
        'start_date' => now(),
        'maturity_date' => now()->addMonths(6),
        'status' => FixedDeposit::STATUS_ACTIVE,
    ]);

    expect($maturedFd->isDueForMaturity())->toBeTrue()
        ->and($activeFd->isDueForMaturity())->toBeFalse();
});
