<?php

use App\Models\Tenant\AtmTerminal;
use App\Models\Tenant\BaselReport;
use App\Models\Tenant\Card;
use App\Models\Tenant\CurrentAccount;
use App\Models\Tenant\FxRate;
use App\Models\Tenant\InterbankSettlement;
use App\Services\Tenant\CurrentAccountService;
use App\Services\Tenant\InterbankService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->accountService = new CurrentAccountService;
    $this->interbankService = new InterbankService;
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 5.1: CURRENT ACCOUNTS
// ═══════════════════════════════════════════════════════════════

it('opens a current account', function () {
    $account = $this->accountService->openAccount([
        'member_id' => 1,
        'account_holder' => 'John Doe Ltd',
        'account_type' => CurrentAccount::TYPE_BUSINESS,
        'overdraft_limit' => 5000000,
    ]);

    expect($account)->toBeInstanceOf(CurrentAccount::class)
        ->and($account->account_number)->toStartWith('BIZ-')
        ->and($account->status)->toBe('active')
        ->and((float) $account->overdraft_limit)->toBe(5000000.0);
});

it('computes effective available balance with overdraft', function () {
    $account = CurrentAccount::create([
        'account_number' => 'CA-TEST-001',
        'member_id' => 1,
        'account_holder' => 'Test',
        'available_balance' => 1000000,
        'overdraft_limit' => 500000,
        'status' => 'active',
    ]);

    expect($account->effective_available)->toBe(1500000.0)
        ->and($account->hasSufficientFunds(1500000))->toBeTrue()
        ->and($account->hasSufficientFunds(1500001))->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 5.2: FX & CARDS
// ═══════════════════════════════════════════════════════════════

it('stores and converts FX rates', function () {
    FxRate::create([
        'base_currency' => 'UGX',
        'quote_currency' => 'USD',
        'buy_rate' => 3700,
        'sell_rate' => 3750,
        'mid_rate' => 3725,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $rate = FxRate::getLatest('USD');
    expect($rate)->not->toBeNull()
        ->and($rate->convert(100, 'sell'))->toBe(375000.0);
});

it('provides FX conversion quotes', function () {
    FxRate::create([
        'base_currency' => 'UGX',
        'quote_currency' => 'EUR',
        'buy_rate' => 4100,
        'sell_rate' => 4200,
        'mid_rate' => 4150,
        'effective_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $quote = $this->accountService->getConversionQuote(500, 'EUR', 'UGX', 'sell');

    expect($quote)->not->toBeNull()
        ->and($quote['converted'])->toBe(2100000.0)
        ->and($quote['rate'])->toBe(4200.0);
});

it('issues a card', function () {
    $card = $this->accountService->issueCard([
        'member_id' => 1,
        'cardholder_name' => 'John Doe',
        'card_type' => Card::TYPE_DEBIT,
        'card_scheme' => 'visa',
        'daily_limit' => 2000000,
        'expiry_date' => now()->addYears(3)->toDateString(),
    ]);

    expect($card)->toBeInstanceOf(Card::class)
        ->and($card->masked_pan)->toStartWith('XXXX-XXXX-XXXX-')
        ->and($card->status)->toBe('active');
});

it('blocks a card with reason', function () {
    $card = $this->accountService->issueCard([
        'member_id' => 1,
        'cardholder_name' => 'Test',
        'card_type' => Card::TYPE_DEBIT,
        'expiry_date' => now()->addYear()->toDateString(),
    ]);

    $blocked = $this->accountService->blockCard($card->id, 'Suspected fraud');

    expect($blocked->status)->toBe('blocked')
        ->and($blocked->block_reason)->toBe('Suspected fraud')
        ->and($blocked->blocked_at)->not->toBeNull();
});

it('detects expired cards', function () {
    $card = Card::create([
        'card_number' => '4111222233334444',
        'masked_pan' => 'XXXX-XXXX-XXXX-4444',
        'member_id' => 1,
        'cardholder_name' => 'Expired',
        'card_type' => 'debit',
        'expiry_date' => now()->subDay()->toDateString(),
        'status' => 'active',
    ]);

    expect($card->isExpired())->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 5.3: ATM & INTERBANK
// ═══════════════════════════════════════════════════════════════

it('detects ATM cash replenishment needs', function () {
    $atm = AtmTerminal::create([
        'terminal_id' => 'ATM-001',
        'terminal_name' => 'Main Branch ATM',
        'location' => 'Kampala',
        'current_cash' => 3000000,
        'min_cash_alert' => 5000000,
        'status' => 'online',
    ]);

    expect($atm->needsReplenishment())->toBeTrue();

    $atm->replenish(20000000);
    expect($atm->fresh()->needsReplenishment())->toBeFalse()
        ->and((float) $atm->fresh()->current_cash)->toBe(23000000.0);
});

it('initiates and settles an interbank transaction', function () {
    $settlement = $this->interbankService->initiateSettlement([
        'settlement_type' => InterbankSettlement::TYPE_EFT,
        'originating_bank' => 'Our SACCO',
        'originating_account' => '001-001-001',
        'receiving_bank' => 'Stanbic Bank',
        'receiving_account' => '9020011223344',
        'amount' => 5000000,
        'value_date' => now()->toDateString(),
    ]);

    expect($settlement->settlement_ref)->toStartWith('IBS-')
        ->and($settlement->status)->toBe('pending');

    $settled = $this->interbankService->settle($settlement->id);
    expect($settled->status)->toBe('settled')
        ->and($settled->settled_at)->not->toBeNull();
});

it('generates a settlement summary', function () {
    $s1 = $this->interbankService->initiateSettlement([
        'settlement_type' => 'eft',
        'originating_bank' => 'A',
        'originating_account' => '1',
        'receiving_bank' => 'B',
        'receiving_account' => '2',
        'amount' => 1000000,
        'value_date' => now()->toDateString(),
    ]);
    $this->interbankService->settle($s1->id);

    $this->interbankService->initiateSettlement([
        'settlement_type' => 'rtgs',
        'originating_bank' => 'A',
        'originating_account' => '1',
        'receiving_bank' => 'C',
        'receiving_account' => '3',
        'amount' => 2000000,
        'value_date' => now()->toDateString(),
    ]);

    $summary = $this->interbankService->getSettlementSummary();

    expect($summary['total'])->toBe(2)
        ->and($summary['settled'])->toBe(1)
        ->and($summary['pending'])->toBe(1)
        ->and($summary['total_amount'])->toBe(3000000.0);
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 5.4: BASEL III REPORTING
// ═══════════════════════════════════════════════════════════════

it('generates a Basel III capital adequacy report', function () {
    $report = $this->interbankService->generateBaselReport([
        'report_type' => BaselReport::TYPE_CAPITAL_ADEQUACY,
        'reporting_period' => '2026-Q1',
        'tier_1_capital' => 50000000000,
        'tier_2_capital' => 10000000000,
        'risk_weighted_assets' => 400000000000,
        'minimum_car' => 12.0,
    ]);

    // CAR = (50B + 10B) / 400B × 100 = 15%
    expect($report)->toBeInstanceOf(BaselReport::class)
        ->and($report->report_ref)->toStartWith('BSL-')
        ->and((float) $report->total_capital)->toBe(60000000000.0)
        ->and((float) $report->car_ratio)->toBe(15.0)
        ->and($report->is_compliant)->toBeTrue();
});

it('detects non-compliant CAR below BOU minimum', function () {
    $report = $this->interbankService->generateBaselReport([
        'report_type' => BaselReport::TYPE_CAPITAL_ADEQUACY,
        'reporting_period' => '2026-Q2',
        'tier_1_capital' => 20000000000,
        'tier_2_capital' => 5000000000,
        'risk_weighted_assets' => 400000000000,
        'minimum_car' => 12.0,
    ]);

    // CAR = 25B / 400B × 100 = 6.25% → non-compliant
    expect((float) $report->car_ratio)->toBe(6.25)
        ->and($report->is_compliant)->toBeFalse();
});

it('computes LCR ratio', function () {
    $report = BaselReport::create([
        'report_ref' => 'BSL-LCR-01',
        'report_type' => BaselReport::TYPE_LIQUIDITY_COVERAGE,
        'reporting_period' => '2026-03',
        'hqla' => 100000000000,
        'net_cash_outflows' => 80000000000,
    ]);

    $lcr = $report->computeLcr();

    // LCR = 100B / 80B × 100 = 125%
    expect($lcr)->toBe(125.0);
});
