<?php

namespace App\Services\Tenant;

use App\Models\Tenant\TellerShift;
use App\Models\Tenant\TellerTransaction;
use Illuminate\Support\Str;

/**
 * Branch operations service (FR-CH-001–004).
 *
 * Teller shifts, cash management, transaction limits, EOD reports.
 */
class BranchOperationsService
{
    /**
     * Open a new teller shift (FR-CH-001).
     *
     * @param  array<string, mixed>  $data
     */
    public function openShift(array $data): TellerShift
    {
        $shiftNum = 'SH-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

        return TellerShift::create(array_merge($data, [
            'shift_number' => $shiftNum,
            'status' => TellerShift::STATUS_OPEN,
            'opened_at' => now(),
        ]));
    }

    /**
     * Record a teller transaction (FR-CH-001).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordTransaction(int $shiftId, array $data): TellerTransaction
    {
        $shift = TellerShift::findOrFail($shiftId);

        if (! $shift->isOpen()) {
            throw new \RuntimeException('Cannot transact on a closed shift.');
        }

        $type = $data['transaction_type'];
        $amount = (float) $data['amount'];

        // Check limits (FR-CH-002)
        $requiresApproval = $shift->exceedsLimit($type, $amount);

        $ref = 'TT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        $txn = TellerTransaction::create(array_merge($data, [
            'transaction_ref' => $ref,
            'shift_id' => $shiftId,
            'teller_id' => $shift->teller_id,
            'teller_name' => $shift->teller_name,
            'requires_approval' => $requiresApproval,
            'status' => $requiresApproval
                ? TellerTransaction::STATUS_PENDING
                : TellerTransaction::STATUS_COMPLETED,
        ]));

        // Update shift totals
        if (! $requiresApproval) {
            $this->updateShiftTotals($shift, $type, $amount);
        }

        return $txn;
    }

    /**
     * Cash transfer between tellers (FR-CH-003).
     */
    public function interTellerTransfer(int $fromShiftId, int $toShiftId, float $amount, ?string $narration = null): array
    {
        $fromShift = TellerShift::findOrFail($fromShiftId);
        $toShift = TellerShift::findOrFail($toShiftId);

        $ref = 'ITT-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

        $outTxn = TellerTransaction::create([
            'transaction_ref' => $ref.'-OUT',
            'shift_id' => $fromShiftId,
            'transaction_type' => TellerTransaction::TYPE_TRANSFER_OUT,
            'teller_id' => $fromShift->teller_id,
            'teller_name' => $fromShift->teller_name,
            'amount' => $amount,
            'counterpart_teller_id' => $toShift->teller_id,
            'counterpart_branch' => $toShift->branch_code,
            'status' => TellerTransaction::STATUS_COMPLETED,
            'narration' => $narration,
        ]);

        $inTxn = TellerTransaction::create([
            'transaction_ref' => $ref.'-IN',
            'shift_id' => $toShiftId,
            'transaction_type' => TellerTransaction::TYPE_TRANSFER_IN,
            'teller_id' => $toShift->teller_id,
            'teller_name' => $toShift->teller_name,
            'amount' => $amount,
            'counterpart_teller_id' => $fromShift->teller_id,
            'counterpart_branch' => $fromShift->branch_code,
            'status' => TellerTransaction::STATUS_COMPLETED,
            'narration' => $narration,
        ]);

        $this->updateShiftTotals($fromShift, TellerTransaction::TYPE_TRANSFER_OUT, $amount);
        $this->updateShiftTotals($toShift, TellerTransaction::TYPE_TRANSFER_IN, $amount);

        return ['out' => $outTxn, 'in' => $inTxn];
    }

    /**
     * Close a shift and compute variance (FR-CH-004).
     */
    public function closeShift(int $shiftId, float $actualBalance, ?string $notes = null): TellerShift
    {
        $shift = TellerShift::findOrFail($shiftId);
        $shift->closeShift($actualBalance, $notes);

        return $shift->fresh();
    }

    /**
     * EOD report for a branch (FR-CH-004).
     *
     * @return array{branch: string, shifts: int, total_deposits: float, total_withdrawals: float, total_variance: float, open_shifts: int}
     */
    public function getEodReport(string $branchCode, ?string $date = null): array
    {
        $date ??= now()->toDateString();

        $shifts = TellerShift::query()
            ->forBranch($branchCode)
            ->whereDate('opened_at', $date)
            ->get();

        return [
            'branch' => $branchCode,
            'date' => $date,
            'shifts' => $shifts->count(),
            'total_deposits' => round($shifts->sum('total_deposits'), 2),
            'total_withdrawals' => round($shifts->sum('total_withdrawals'), 2),
            'total_variance' => round($shifts->whereNotNull('variance')->sum('variance'), 2),
            'open_shifts' => $shifts->where('status', TellerShift::STATUS_OPEN)->count(),
        ];
    }

    private function updateShiftTotals(TellerShift $shift, string $type, float $amount): void
    {
        match ($type) {
            TellerTransaction::TYPE_DEPOSIT => $shift->increment('total_deposits', $amount),
            TellerTransaction::TYPE_WITHDRAWAL => $shift->increment('total_withdrawals', $amount),
            TellerTransaction::TYPE_TRANSFER_IN => $shift->increment('total_transfers_in', $amount),
            TellerTransaction::TYPE_TRANSFER_OUT => $shift->increment('total_transfers_out', $amount),
            default => null,
        };
    }
}
