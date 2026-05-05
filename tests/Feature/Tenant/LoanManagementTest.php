<?php

use App\Models\Tenant\AmortisationSchedule;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanApplication;
use App\Models\Tenant\LoanGuarantor;
use App\Models\Tenant\LoanProduct;
use App\Models\Tenant\Member;
use App\Models\Tenant\SavingsAccount;
use App\Models\Tenant\SavingsProduct;
use App\Services\Tenant\LoanService;
use Carbon\Carbon;

beforeEach(function () {
    $this->initializeTenancy();
    $this->loanService = new LoanService;
});

// ─── Helpers ────────────────────────────────────────────────────

function createProduct(array $overrides = []): LoanProduct
{
    return LoanProduct::create(array_merge([
        'product_code' => 'LP-'.fake()->unique()->word(),
        'product_name' => 'Standard Term Loan',
        'product_type' => LoanProduct::TYPE_TERM,
        'interest_rate' => 24.0000, // 24% p.a.
        'interest_method' => LoanProduct::METHOD_REDUCING,
        'interest_period' => 'monthly',
        'minimum_tenure_months' => 1,
        'maximum_tenure_months' => 60,
        'minimum_guarantors' => 1,
        'four_eyes_disbursement' => true,
        'is_active' => true,
    ], $overrides));
}

function createLoan(LoanProduct $product, Member $member, array $overrides = []): Loan
{
    return Loan::create(array_merge([
        'loan_number' => 'LN-'.fake()->unique()->numerify('######'),
        'member_id' => $member->id,
        'product_id' => $product->id,
        'principal_amount' => 1000000.00,
        'approved_amount' => 1000000.00,
        'disbursed_amount' => 1000000.00,
        'tenure_months' => 12,
        'interest_rate' => 24.0000,
        'interest_method' => LoanProduct::METHOD_REDUCING,
        'outstanding_principal' => 1000000.00,
        'outstanding_interest' => 0.00,
        'outstanding_penalty' => 0.00,
        'total_outstanding' => 1000000.00,
        'monthly_instalment' => 94560.00,
        'status' => Loan::STATUS_ACTIVE,
        'first_repayment_date' => now()->addMonth()->toDateString(),
        'expected_maturity_date' => now()->addMonths(12)->toDateString(),
    ], $overrides));
}

function createSavingsAccount(Member $member): SavingsAccount
{
    $savingsProduct = SavingsProduct::create([
        'product_code' => 'SP-'.fake()->unique()->word(),
        'product_name' => 'Regular Savings',
        'product_type' => 'regular',
        'interest_rate' => 6.0000,
        'interest_computation' => 'daily_average',
        'interest_posting_cycle' => 'monthly',
        'minimum_balance' => 0,
        'is_active' => true,
    ]);

    return SavingsAccount::create([
        'account_number' => 'SAV-'.fake()->unique()->numerify('######'),
        'member_id' => $member->id,
        'product_id' => $savingsProduct->id,
        'ledger_balance' => 500000.00,
        'available_balance' => 500000.00,
        'held_amount' => 0.00,
        'accrued_interest' => 0.00,
        'status' => SavingsAccount::STATUS_ACTIVE,
    ]);
}

// ─── FR-LM-001: Loan product config ──────────────────────────

it('can create a loan product with all configuration', function () {
    $product = createProduct([
        'processing_fee_rate' => 2.0,
        'grace_period_days' => 5,
        'collateral_required' => true,
    ]);

    expect($product)->toBeInstanceOf(LoanProduct::class)
        ->and($product->product_type)->toBe(LoanProduct::TYPE_TERM)
        ->and((float) $product->interest_rate)->toBe(24.0)
        ->and($product->four_eyes_disbursement)->toBeTrue()
        ->and($product->collateral_required)->toBeTrue();
});

it('computes processing fee correctly (percentage takes precedence over fixed when higher)', function () {
    $product = createProduct(['processing_fee_rate' => 2.0, 'processing_fee_fixed' => 5000]);

    // 2% of 1,000,000 = 20,000 > 5,000 fixed
    expect($product->computeProcessingFee(1000000))->toBe(20000.0);

    // 2% of 200,000 = 4,000 < 5,000 fixed → fixed wins
    expect($product->computeProcessingFee(200000))->toBe(5000.0);
});

// ─── FR-LM-002: Amortisation schedule ────────────────────────

it('generates reducing balance amortisation schedule for 12 months', function () {
    $schedule = $this->loanService->generateSchedule(
        principal: 1_000_000,
        annualRatePercent: 24,
        tenureMonths: 12,
        firstRepaymentDate: Carbon::parse('2026-04-01'),
        method: LoanProduct::METHOD_REDUCING
    );

    expect($schedule)->toHaveCount(12)
        ->and($schedule->first()['instalment_number'])->toBe(1)
        ->and($schedule->first()['opening_balance'])->toBe(1_000_000.0)
        ->and($schedule->last()['closing_balance'])->toEqual(0);
});

it('generates flat rate amortisation schedule', function () {
    $schedule = $this->loanService->generateSchedule(
        principal: 600_000,
        annualRatePercent: 12,
        tenureMonths: 6,
        firstRepaymentDate: Carbon::parse('2026-04-01'),
        method: LoanProduct::METHOD_FLAT
    );

    expect($schedule)->toHaveCount(6);

    // Flat rate: total interest = 600,000 × 12% × (6/12) = 36,000
    // Monthly instalment = (600,000 + 36,000) / 6 = 106,000
    $totalPrincipal = $schedule->sum('principal_due');
    $totalInterest = $schedule->sum('interest_due');

    expect($totalPrincipal)->toBeGreaterThan(599_990)->toBeLessThan(600_010)
        ->and($totalInterest)->toBeGreaterThan(35_990)->toBeLessThan(36_010);
});

it('last instalment clears the remaining balance on reducing schedule', function () {
    $schedule = $this->loanService->generateSchedule(
        principal: 500_000,
        annualRatePercent: 18,
        tenureMonths: 6,
        firstRepaymentDate: Carbon::parse('2026-04-01'),
        method: LoanProduct::METHOD_REDUCING
    );

    expect((float) $schedule->last()['closing_balance'])->toEqual(0.0);
});

// ─── FR-LM-010: DSCR computation ─────────────────────────────

it('computes DSCR correctly for eligible applicant', function () {
    $member = Member::factory()->create();
    $product = createProduct();

    $application = LoanApplication::create([
        'application_ref' => 'APP-001',
        'member_id' => $member->id,
        'product_id' => $product->id,
        'amount_requested' => 1000000,
        'tenure_months_requested' => 12,
        'monthly_income' => 3000000,
        'monthly_expenses' => 1000000,
        'status' => LoanApplication::STATUS_SUBMITTED,
    ]);

    // Net income = 3,000,000 - 1,000,000 = 2,000,000
    // DSCR = 2,000,000 / (94,560 instalment) ≈ 21.15 — very healthy
    $dscr = $application->computeDscr(94560);

    expect($dscr)->toBeGreaterThan(1.25);
});

it('flags insufficient DSCR', function () {
    $member = Member::factory()->create();
    $product = createProduct();

    $application = LoanApplication::create([
        'application_ref' => 'APP-002',
        'member_id' => $member->id,
        'product_id' => $product->id,
        'amount_requested' => 1000000,
        'tenure_months_requested' => 12,
        'monthly_income' => 200000,
        'monthly_expenses' => 180000,
        'status' => LoanApplication::STATUS_SUBMITTED,
    ]);

    // Net income = 20,000 / 94,560 = 0.21 — below 1.25 threshold
    $dscr = $application->computeDscr(94560);

    expect($dscr)->toBeLessThan(1.25);
});

// ─── FR-LM-020: Guarantor eligibility ────────────────────────

it('validates guarantor with sufficient savings balance', function () {
    $member = Member::factory()->create();
    $account = createSavingsAccount($member);

    $blocks = $this->loanService->validateGuarantorEligibility($account, 300000);

    expect($blocks)->toBeEmpty();
});

it('blocks guarantor with insufficient savings', function () {
    $member = Member::factory()->create();
    $account = createSavingsAccount($member);

    $blocks = $this->loanService->validateGuarantorEligibility($account, 600000);

    expect($blocks)->not->toBeEmpty()
        ->and($blocks[0])->toContain('available savings balance');
});

it('blocks guarantor whose savings account is not active', function () {
    $member = Member::factory()->create();
    $account = createSavingsAccount($member);
    $account->update(['status' => SavingsAccount::STATUS_DORMANT]);

    $blocks = $this->loanService->validateGuarantorEligibility($account, 100000);

    expect($blocks)->not->toBeEmpty()
        ->and($blocks[0])->toContain('not active');
});

// ─── FR-LM-021: Guarantor lock / release ─────────────────────

it('locks guarantor savings on disbursement', function () {
    $guarantorMember = Member::factory()->create();
    $account = createSavingsAccount($guarantorMember);

    $borrower = Member::factory()->create();
    $product = createProduct();
    $loan = createLoan($product, $borrower);

    $guarantor = LoanGuarantor::create([
        'loan_id' => $loan->id,
        'guarantor_member_id' => $guarantorMember->id,
        'guaranteed_savings_account_id' => $account->id,
        'guaranteed_amount' => 300000,
        'status' => LoanGuarantor::STATUS_ACTIVE,
    ]);

    $this->loanService->lockGuarantorSavings($guarantor);

    expect($account->fresh())
        ->held_amount->toEqual('300000.00')
        ->available_balance->toEqual('200000.00');
});

it('releases guarantor savings on loan completion', function () {
    $guarantorMember = Member::factory()->create();
    $account = createSavingsAccount($guarantorMember);
    // Pre-apply a hold as if lock was done
    $account->applyHold(300000);

    $borrower = Member::factory()->create();
    $product = createProduct();
    $loan = createLoan($product, $borrower);

    $guarantor = LoanGuarantor::create([
        'loan_id' => $loan->id,
        'guarantor_member_id' => $guarantorMember->id,
        'guaranteed_savings_account_id' => $account->id,
        'guaranteed_amount' => 300000,
        'locked_amount' => 300000,
        'status' => LoanGuarantor::STATUS_ACTIVE,
    ]);

    $this->loanService->releaseGuarantorSavings($guarantor, 'Loan repaid in full');

    expect($guarantor->fresh()->status)->toBe(LoanGuarantor::STATUS_RELEASED)
        ->and((float) $guarantor->fresh()->locked_amount)->toBe(0.0)
        ->and($account->fresh()->held_amount)->toEqual('0.00');
});

// ─── FR-LM-030/031: Repayment allocation ─────────────────────

it('allocates repayment in order: penalty → interest → principal', function () {
    $member = Member::factory()->create();
    $product = createProduct();

    $loan = createLoan($product, $member, [
        'outstanding_principal' => 800000,
        'outstanding_interest' => 15000,
        'outstanding_penalty' => 5000,
        'total_outstanding' => 820000,
    ]);

    $repayment = $this->loanService->processRepayment($loan, 120000);

    // Should clear penalty (5,000) first, then interest (15,000), then principal (100,000)
    expect((float) $repayment->allocated_to_penalty)->toBe(5000.0)
        ->and((float) $repayment->allocated_to_interest)->toBe(15000.0)
        ->and((float) $repayment->allocated_to_principal)->toBe(100000.0)
        ->and((float) $repayment->excess_amount)->toBe(0.0);

    expect((float) $loan->fresh()->outstanding_penalty)->toBe(0.0)
        ->and((float) $loan->fresh()->outstanding_interest)->toBe(0.0)
        ->and((float) $loan->fresh()->outstanding_principal)->toBe(700000.0);
});

it('marks loan as completed when fully paid', function () {
    $member = Member::factory()->create();
    $product = createProduct();

    $loan = createLoan($product, $member, [
        'outstanding_principal' => 5000,
        'outstanding_interest' => 200,
        'outstanding_penalty' => 0,
        'total_outstanding' => 5200,
    ]);

    $this->loanService->processRepayment($loan, 5200);

    expect($loan->fresh()->status)->toBe(Loan::STATUS_COMPLETED)
        ->and((float) $loan->fresh()->total_outstanding)->toBe(0.0);
});

it('records excess payment correctly', function () {
    $member = Member::factory()->create();
    $product = createProduct();

    $loan = createLoan($product, $member, [
        'outstanding_principal' => 1000,
        'outstanding_interest' => 0,
        'outstanding_penalty' => 0,
        'total_outstanding' => 1000,
    ]);

    $repayment = $this->loanService->processRepayment($loan, 1500);

    expect((float) $repayment->excess_amount)->toBe(500.0)
        ->and((float) $loan->fresh()->total_outstanding)->toBe(0.0);
});

// ─── FR-LM-032: PAR recomputation ───────────────────────────

it('reports current PAR bucket when no instalments are overdue', function () {
    $member = Member::factory()->create();
    $product = createProduct();
    $loan = createLoan($product, $member);

    // Create a future schedule
    AmortisationSchedule::create([
        'loan_id' => $loan->id,
        'instalment_number' => 1,
        'due_date' => now()->addMonth(),
        'principal_due' => 80000,
        'interest_due' => 20000,
        'total_due' => 100000,
        'opening_balance' => 1000000,
        'closing_balance' => 920000,
        'status' => AmortisationSchedule::STATUS_SCHEDULED,
    ]);

    $this->loanService->recomputePar($loan);

    expect($loan->fresh()->par_bucket)->toBe('current')
        ->and($loan->fresh()->days_past_due)->toBe(0);
});

it('correctly assigns PAR bucket for overdue instalment', function () {
    $member = Member::factory()->create();
    $product = createProduct();
    $loan = createLoan($product, $member);

    // Overdue by 45 days → should fall in 31-60 bucket
    AmortisationSchedule::create([
        'loan_id' => $loan->id,
        'instalment_number' => 1,
        'due_date' => now()->subDays(45)->toDateString(),
        'principal_due' => 80000,
        'interest_due' => 20000,
        'total_due' => 100000,
        'opening_balance' => 1000000,
        'closing_balance' => 920000,
        'status' => AmortisationSchedule::STATUS_SCHEDULED,
    ]);

    $this->loanService->recomputePar($loan);

    expect($loan->fresh()->par_bucket)->toBe('31-60')
        ->and($loan->fresh()->days_past_due)->toBeGreaterThanOrEqual(44);
});

it('computes par bucket label correctly for all ranges', function () {
    expect(Loan::computeParBucket(0))->toBe('current')
        ->and(Loan::computeParBucket(1))->toBe('1-30')
        ->and(Loan::computeParBucket(30))->toBe('1-30')
        ->and(Loan::computeParBucket(31))->toBe('31-60')
        ->and(Loan::computeParBucket(61))->toBe('61-90')
        ->and(Loan::computeParBucket(91))->toBe('91-180')
        ->and(Loan::computeParBucket(181))->toBe('180+');
});
