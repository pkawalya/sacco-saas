<?php

namespace App\Services\Tenant;

use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\CostAllocation;
use App\Models\Tenant\CostCentre;
use Illuminate\Support\Collection;

/**
 * Cost Centre service.
 *
 * FR-CC-001: 4-level hierarchy management
 * FR-CC-002: CRUD with historical data preservation
 * FR-CC-003: Internal charge-backs with transfer pricing
 * FR-CC-004: Cost Centre P&L reporting
 */
class CostCentreService
{
    // ─── Hierarchy (FR-CC-001) ──────────────────────────────────

    /**
     * Create a cost centre with hierarchy validation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \RuntimeException
     */
    public function createCostCentre(array $data): CostCentre
    {
        $cc = new CostCentre($data);
        $cc->validateHierarchy();

        $cc->save();

        return $cc;
    }

    /**
     * Get the full hierarchy as a nested tree.
     *
     * @return Collection<int, CostCentre>
     */
    public function getHierarchyTree(): Collection
    {
        return CostCentre::query()
            ->roots()
            ->with('descendants')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get a flat list with indentation info for dropdowns.
     *
     * @return Collection<int, array{id: int, code: string, name: string, level: int, path: string, is_active: bool}>
     */
    public function getFlatHierarchy(): Collection
    {
        return CostCentre::query()
            ->orderBy('level')
            ->orderBy('code')
            ->get()
            ->map(fn (CostCentre $cc): array => [
                'id' => $cc->id,
                'code' => $cc->code,
                'name' => $cc->name,
                'level' => $cc->level,
                'path' => $cc->path,
                'is_active' => $cc->is_active,
            ]);
    }

    // ─── Deactivation (FR-CC-002) ──────────────────────────────

    /**
     * Deactivate a cost centre and optionally its children.
     *
     * @throws \RuntimeException
     */
    public function deactivate(CostCentre $costCentre, int $userId, string $reason, bool $cascadeToChildren = false): void
    {
        // Check for active allocations
        $activeAllocations = $costCentre->allocations()->active()->count();
        if ($activeAllocations > 0 && ! $cascadeToChildren) {
            throw new \RuntimeException(
                "Cannot deactivate: {$activeAllocations} active allocation(s) exist. Freeze or close them first, or use cascade."
            );
        }

        $costCentre->deactivate($userId, $reason);

        if ($cascadeToChildren) {
            foreach ($costCentre->children as $child) {
                if ($child->is_active) {
                    $this->deactivate($child, $userId, "Cascaded from parent: {$costCentre->code}", true);
                }
            }
        }
    }

    // ─── Charge-backs (FR-CC-003) ──────────────────────────────

    /**
     * Create an internal charge-back between cost centres.
     *
     * @throws \RuntimeException
     */
    public function createChargeback(
        int $fromCostCentreId,
        int $toCostCentreId,
        int $glAccountId,
        float $amount,
        int $fiscalYear,
        ?int $periodMonth = null,
        ?string $description = null,
    ): CostAllocation {
        $from = CostCentre::findOrFail($fromCostCentreId);
        $to = CostCentre::findOrFail($toCostCentreId);

        if ($from->id === $to->id) {
            throw new \RuntimeException('Cannot create a charge-back to the same cost centre.');
        }

        if (! $from->is_active || ! $to->is_active) {
            throw new \RuntimeException('Both cost centres must be active for charge-backs.');
        }

        return CostAllocation::create([
            'cost_centre_id' => $toCostCentreId,
            'gl_account_id' => $glAccountId,
            'fiscal_year' => $fiscalYear,
            'period_month' => $periodMonth,
            'allocated_amount' => $amount,
            'actual_amount' => $amount,
            'allocation_method' => CostAllocation::METHOD_DIRECT,
            'allocation_percentage' => 100,
            'chargeback_from_id' => $fromCostCentreId,
            'transfer_price' => $amount,
            'chargeback_description' => $description ?? "Charge-back from {$from->code} to {$to->code}",
            'status' => CostAllocation::STATUS_ACTIVE,
        ]);
    }

    // ─── P&L Report (FR-CC-004) ────────────────────────────────

    /**
     * Generate a Cost Centre P&L report reconcilable to the Income Statement.
     *
     * @return array{cost_centre: string, revenues: Collection, expenses: Collection, total_revenue: float, total_expense: float, net_income: float}
     */
    public function getCostCentrePnL(int $costCentreId, int $fiscalYear): array
    {
        $cc = CostCentre::findOrFail($costCentreId);

        $allocations = CostAllocation::query()
            ->where('cost_centre_id', $costCentreId)
            ->forYear($fiscalYear)
            ->with('glAccount')
            ->get();

        $revenues = $allocations->filter(
            fn (CostAllocation $a): bool => $a->glAccount->account_type === ChartOfAccount::TYPE_REVENUE
        );
        $expenses = $allocations->filter(
            fn (CostAllocation $a): bool => $a->glAccount->account_type === ChartOfAccount::TYPE_EXPENSE
        );

        $totalRevenue = $revenues->sum('actual_amount');
        $totalExpense = $expenses->sum('actual_amount');

        return [
            'cost_centre' => $cc->code.' – '.$cc->name,
            'revenues' => $revenues->map(fn (CostAllocation $a): array => [
                'gl_code' => $a->glAccount->account_code,
                'gl_name' => $a->glAccount->account_name,
                'allocated' => (float) $a->allocated_amount,
                'actual' => (float) $a->actual_amount,
                'variance' => $a->variance,
            ]),
            'expenses' => $expenses->map(fn (CostAllocation $a): array => [
                'gl_code' => $a->glAccount->account_code,
                'gl_name' => $a->glAccount->account_name,
                'allocated' => (float) $a->allocated_amount,
                'actual' => (float) $a->actual_amount,
                'variance' => $a->variance,
            ]),
            'total_revenue' => round($totalRevenue, 2),
            'total_expense' => round($totalExpense, 2),
            'net_income' => round($totalRevenue - $totalExpense, 2),
        ];
    }

    /**
     * Consolidated P&L across all cost centres for a fiscal year.
     *
     * @return Collection<int, array{code: string, name: string, total_revenue: float, total_expense: float, net_income: float}>
     */
    public function getConsolidatedPnL(int $fiscalYear): Collection
    {
        $costCentres = CostCentre::query()
            ->active()
            ->with(['allocations' => fn ($q) => $q->forYear($fiscalYear)->with('glAccount')])
            ->orderBy('code')
            ->get();

        return $costCentres->map(function (CostCentre $cc): array {
            $revenues = $cc->allocations->filter(
                fn (CostAllocation $a): bool => $a->glAccount->account_type === ChartOfAccount::TYPE_REVENUE
            )->sum('actual_amount');

            $expenses = $cc->allocations->filter(
                fn (CostAllocation $a): bool => $a->glAccount->account_type === ChartOfAccount::TYPE_EXPENSE
            )->sum('actual_amount');

            return [
                'code' => $cc->code,
                'name' => $cc->name,
                'level' => $cc->level,
                'total_revenue' => round($revenues, 2),
                'total_expense' => round($expenses, 2),
                'net_income' => round($revenues - $expenses, 2),
            ];
        });
    }

    /**
     * Allocation summary by method for a fiscal year.
     *
     * @return Collection<string, array{method: string, count: int, total_allocated: float, total_actual: float}>
     */
    public function getAllocationSummary(int $fiscalYear): Collection
    {
        return CostAllocation::query()
            ->forYear($fiscalYear)
            ->get()
            ->groupBy('allocation_method')
            ->map(fn (Collection $group, string $method): array => [
                'method' => CostAllocation::METHODS[$method] ?? $method,
                'count' => $group->count(),
                'total_allocated' => round($group->sum('allocated_amount'), 2),
                'total_actual' => round($group->sum('actual_amount'), 2),
            ]);
    }
}
