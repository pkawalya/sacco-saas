<?php

namespace App\Services\Tenant;

use App\Models\Tenant\BaselReport;
use App\Models\Tenant\InterbankSettlement;
use Illuminate\Support\Str;

/**
 * Interbank settlement and Basel III reporting service.
 */
class InterbankService
{
    /**
     * Initiate an interbank settlement.
     *
     * @param  array<string, mixed>  $data
     */
    public function initiateSettlement(array $data): InterbankSettlement
    {
        $ref = 'IBS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        return InterbankSettlement::create(array_merge($data, [
            'settlement_ref' => $ref,
            'initiated_at' => now(),
            'status' => 'pending',
        ]));
    }

    /**
     * Settle a pending transaction.
     */
    public function settle(int $settlementId): InterbankSettlement
    {
        $settlement = InterbankSettlement::findOrFail($settlementId);
        $settlement->markSettled();

        return $settlement->fresh();
    }

    /**
     * Generate Basel III capital adequacy report.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateBaselReport(array $data): BaselReport
    {
        $ref = 'BSL-'.($data['reporting_period'] ?? now()->format('Y-m'));

        $totalCapital = ($data['tier_1_capital'] ?? 0) + ($data['tier_2_capital'] ?? 0);

        $report = BaselReport::create(array_merge($data, [
            'report_ref' => $ref,
            'total_capital' => $totalCapital,
        ]));

        // Auto-compute CAR
        if ((float) ($data['risk_weighted_assets'] ?? 0) > 0) {
            $report->computeCar();
        }

        // Auto-compute LCR
        if ((float) ($data['net_cash_outflows'] ?? 0) > 0) {
            $report->computeLcr();
        }

        return $report->fresh();
    }

    /**
     * Get settlement summary for a period.
     *
     * @return array{total: int, settled: int, pending: int, failed: int, total_amount: float}
     */
    public function getSettlementSummary(?string $date = null): array
    {
        $query = InterbankSettlement::query();

        if ($date) {
            $query->whereDate('initiated_at', $date);
        }

        $all = $query->get();

        return [
            'total' => $all->count(),
            'settled' => $all->where('status', 'settled')->count(),
            'pending' => $all->where('status', 'pending')->count(),
            'failed' => $all->where('status', 'failed')->count(),
            'total_amount' => round($all->sum('amount'), 2),
        ];
    }
}
