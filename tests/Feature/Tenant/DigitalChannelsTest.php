<?php

use App\Models\Tenant\Agent;
use App\Models\Tenant\AgentTransaction;
use App\Models\Tenant\OfflineSyncQueue;
use App\Models\Tenant\TellerShift;
use App\Models\Tenant\TellerTransaction;
use App\Services\Tenant\AgentBankingService;
use App\Services\Tenant\BranchOperationsService;
use App\Services\Tenant\OfflineSyncService;

beforeEach(function () {
    $this->initializeTenancy();
    $this->branchService = new BranchOperationsService;
    $this->agentService = new AgentBankingService;
    $this->syncService = new OfflineSyncService;
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 3.1: BRANCH OPERATIONS (FR-CH-001–004)
// ═══════════════════════════════════════════════════════════════

it('opens a teller shift', function () {
    $shift = $this->branchService->openShift([
        'teller_id' => 1,
        'teller_name' => 'Alice',
        'branch_code' => 'BR-001',
        'opening_balance' => 5000000,
    ]);

    expect($shift)->toBeInstanceOf(TellerShift::class)
        ->and($shift->shift_number)->toStartWith('SH-')
        ->and($shift->status)->toBe(TellerShift::STATUS_OPEN)
        ->and((float) $shift->opening_balance)->toBe(5000000.0);
});

it('records a deposit transaction within limits', function () {
    $shift = $this->branchService->openShift([
        'teller_id' => 1,
        'teller_name' => 'Alice',
        'branch_code' => 'BR-001',
        'opening_balance' => 5000000,
        'deposit_limit' => 10000000,
    ]);

    $txn = $this->branchService->recordTransaction($shift->id, [
        'transaction_type' => TellerTransaction::TYPE_DEPOSIT,
        'amount' => 500000,
        'member_name' => 'John Doe',
        'narration' => 'Savings deposit',
    ]);

    expect($txn)->toBeInstanceOf(TellerTransaction::class)
        ->and($txn->transaction_ref)->toStartWith('TT-')
        ->and($txn->requires_approval)->toBeFalse()
        ->and($txn->status)->toBe(TellerTransaction::STATUS_COMPLETED)
        ->and((float) $shift->fresh()->total_deposits)->toBe(500000.0);
});

it('flags transactions exceeding limit for approval', function () {
    $shift = $this->branchService->openShift([
        'teller_id' => 1,
        'teller_name' => 'Alice',
        'branch_code' => 'BR-001',
        'opening_balance' => 5000000,
        'deposit_limit' => 1000000,
    ]);

    $txn = $this->branchService->recordTransaction($shift->id, [
        'transaction_type' => TellerTransaction::TYPE_DEPOSIT,
        'amount' => 5000000,
    ]);

    expect($txn->requires_approval)->toBeTrue()
        ->and($txn->status)->toBe(TellerTransaction::STATUS_PENDING);
});

it('performs inter-teller cash transfer', function () {
    $shift1 = $this->branchService->openShift([
        'teller_id' => 1,
        'teller_name' => 'Alice',
        'branch_code' => 'BR-001',
        'opening_balance' => 10000000,
    ]);
    $shift2 = $this->branchService->openShift([
        'teller_id' => 2,
        'teller_name' => 'Bob',
        'branch_code' => 'BR-001',
        'opening_balance' => 2000000,
    ]);

    $result = $this->branchService->interTellerTransfer($shift1->id, $shift2->id, 3000000);

    expect($result)->toHaveKeys(['out', 'in'])
        ->and((float) $shift1->fresh()->total_transfers_out)->toBe(3000000.0)
        ->and((float) $shift2->fresh()->total_transfers_in)->toBe(3000000.0);
});

it('closes shift with variance computation', function () {
    $shift = $this->branchService->openShift([
        'teller_id' => 1,
        'teller_name' => 'Alice',
        'branch_code' => 'BR-001',
        'opening_balance' => 5000000,
    ]);

    // Record a deposit
    $this->branchService->recordTransaction($shift->id, [
        'transaction_type' => TellerTransaction::TYPE_DEPOSIT,
        'amount' => 1000000,
    ]);

    // Expected: 5000000 + 1000000 = 6000000
    // Actual count: 5900000 → variance = -100000
    $closed = $this->branchService->closeShift($shift->id, 5900000, 'Short by 100K');

    expect($closed->status)->toBe(TellerShift::STATUS_CLOSED)
        ->and((float) $closed->closing_balance)->toBe(5900000.0)
        ->and((float) $closed->variance)->toBe(-100000.0)
        ->and($closed->closing_notes)->toBe('Short by 100K');
});

it('computes expected balance correctly', function () {
    $shift = TellerShift::create([
        'shift_number' => 'SH-TEST-001',
        'teller_id' => 1,
        'teller_name' => 'Test',
        'branch_code' => 'BR-001',
        'opening_balance' => 1000000,
        'total_deposits' => 500000,
        'total_withdrawals' => 200000,
        'total_transfers_in' => 100000,
        'total_transfers_out' => 50000,
        'status' => 'open',
        'opened_at' => now(),
    ]);

    // 1000000 + 500000 - 200000 + 100000 - 50000 = 1350000
    expect($shift->expected_balance)->toBe(1350000.0);
});

it('generates an EOD branch report', function () {
    $this->branchService->openShift([
        'teller_id' => 1,
        'teller_name' => 'Alice',
        'branch_code' => 'BR-001',
        'opening_balance' => 5000000,
    ]);

    $report = $this->branchService->getEodReport('BR-001');

    expect($report['branch'])->toBe('BR-001')
        ->and($report['shifts'])->toBe(1)
        ->and($report['open_shifts'])->toBe(1);
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 3.4: AGENT BANKING (FR-CH-030–031)
// ═══════════════════════════════════════════════════════════════

it('registers a new agent', function () {
    $agent = $this->agentService->registerAgent([
        'agent_name' => 'James Shop',
        'business_name' => 'James Mobile Money',
        'phone' => '+256700000001',
        'branch_code' => 'BR-001',
        'commission_rate' => 1.00,
    ]);

    expect($agent)->toBeInstanceOf(Agent::class)
        ->and($agent->agent_code)->toStartWith('AG-')
        ->and($agent->status)->toBe(Agent::STATUS_ACTIVE);
});

it('records agent deposit with commission', function () {
    $agent = $this->agentService->registerAgent([
        'agent_name' => 'Test Agent',
        'commission_rate' => 0.50,
    ]);

    $txn = $this->agentService->recordTransaction($agent->id, [
        'transaction_type' => AgentTransaction::TYPE_DEPOSIT,
        'amount' => 100000,
        'member_name' => 'John Doe',
    ]);

    // Commission = 100000 * 0.50% = 500
    expect($txn->transaction_ref)->toStartWith('AT-')
        ->and((float) $txn->commission_amount)->toBe(500.0)
        ->and((float) $txn->float_after)->toBe(100000.0)
        ->and((float) $agent->fresh()->total_commission_earned)->toBe(500.0);
});

it('rejects withdrawal when insufficient float', function () {
    $agent = $this->agentService->registerAgent([
        'agent_name' => 'Low Float Agent',
        'commission_rate' => 0.50,
    ]);

    expect(fn () => $this->agentService->recordTransaction($agent->id, [
        'transaction_type' => AgentTransaction::TYPE_WITHDRAWAL,
        'amount' => 500000,
    ]))->toThrow(RuntimeException::class, 'Insufficient float balance.');
});

it('computes agent commission correctly', function () {
    $agent = Agent::create([
        'agent_code' => 'AG-COMM-01',
        'agent_name' => 'Test',
        'commission_rate' => 1.50,
        'status' => 'active',
    ]);

    expect($agent->computeCommission(200000))->toBe(3000.0);
});

it('generates agent performance summary', function () {
    $agent = $this->agentService->registerAgent([
        'agent_name' => 'Perf Agent',
        'commission_rate' => 0.50,
    ]);
    $this->agentService->recordTransaction($agent->id, [
        'transaction_type' => AgentTransaction::TYPE_FLOAT_TOP_UP,
        'amount' => 1000000,
    ]);
    $this->agentService->recordTransaction($agent->id, [
        'transaction_type' => AgentTransaction::TYPE_DEPOSIT,
        'amount' => 50000,
        'member_name' => 'M1',
    ]);

    $performance = $this->agentService->getAgentPerformance();
    expect($performance)->toHaveCount(1)
        ->and($performance->first()['total_transactions'])->toBe(2);
});

// ═══════════════════════════════════════════════════════════════
// SPRINT 3.5: OFFLINE OPERATIONS (FR-CH-040–042)
// ═══════════════════════════════════════════════════════════════

it('queues an offline batch', function () {
    $batch = $this->syncService->queueBatch('BR-001', [
        ['type' => 'deposit', 'amount' => 50000],
        ['type' => 'withdrawal', 'amount' => 20000],
    ], 'DEVICE-001');

    expect($batch)->toBeInstanceOf(OfflineSyncQueue::class)
        ->and($batch->sync_status)->toBe(OfflineSyncQueue::STATUS_PENDING)
        ->and($batch->transaction_count)->toBe(2);
});

it('processes pending batches and syncs them', function () {
    $this->syncService->queueBatch('BR-001', [
        ['type' => 'deposit', 'amount' => 50000],
    ], 'DEVICE-A');

    $stats = $this->syncService->processPendingBatches();

    expect($stats['processed'])->toBe(1)
        ->and($stats['synced'])->toBe(1)
        ->and($stats['conflicts'])->toBe(0);
});

it('detects duplicate batch conflicts', function () {
    // First batch - synced
    $batch1 = $this->syncService->queueBatch('BR-001', [
        ['type' => 'deposit', 'amount' => 50000],
    ], 'DEV-DUP');
    $batch1->markSynced();

    // Duplicate batch from same device
    $this->syncService->queueBatch('BR-001', [
        ['type' => 'deposit', 'amount' => 50000],
    ], 'DEV-DUP');

    $stats = $this->syncService->processPendingBatches();

    expect($stats['conflicts'])->toBe(1);
});

it('resolves conflicts with a resolution strategy', function () {
    $batch = $this->syncService->queueBatch('BR-001', [
        ['type' => 'deposit', 'amount' => 50000],
    ], 'DEV-CONFLICT');
    $batch->markConflict(['Duplicate batch detected']);

    $resolved = $this->syncService->resolveConflict($batch->id, OfflineSyncQueue::RESOLUTION_SERVER_WINS, 1);

    expect($resolved->sync_status)->toBe(OfflineSyncQueue::STATUS_SYNCED)
        ->and($resolved->resolution_strategy)->toBe('server_wins')
        ->and($resolved->resolved_by)->toBe(1);
});

it('provides sync health stats', function () {
    $this->syncService->queueBatch('BR-001', [['amount' => 100]], 'D1');
    $batch2 = $this->syncService->queueBatch('BR-002', [['amount' => 200]], 'D2');
    $batch2->markSynced();

    $health = $this->syncService->getSyncHealth();

    expect($health['total_pending'])->toBe(1)
        ->and($health['total_synced'])->toBe(1)
        ->and($health['by_branch'])->toHaveCount(2);
});
