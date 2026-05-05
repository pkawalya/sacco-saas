<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Budget;
use App\Models\Tenant\ExpenseClaim;
use App\Models\Tenant\Investment;
use App\Models\Tenant\RevenueSource;
use Illuminate\Support\Collection;

/**
 * Revenue & Expense Engine service.
 *
 * Covers:
 * - FR-RE-010–014: WHT automation, revenue recognition
 * - FR-RE-020–024: Budget management, expense claims, variance reporting
 * - FR-RE-030–034: Investment portfolio, performance dashboard
 */
class RevenueExpenseService
{
    // ─── Revenue & WHT (FR-RE-010–014) ──────────────────────────

    /**
     * Recognise revenue from a source with automatic WHT computation.
     *
     * @return array{gross: float, wht: float, net: float, source: RevenueSource}
     */
    public function recogniseRevenue(string $sourceCode, float $grossAmount): array
    {
        $source = RevenueSource::where('source_code', $sourceCode)
            ->where('is_active', true)
            ->firstOrFail();

        $wht = $source->computeWht($grossAmount);
        $net = $source->computeNetAmount($grossAmount);

        return [
            'gross' => round($grossAmount, 2),
            'wht' => $wht,
            'net' => $net,
            'source' => $source,
        ];
    }

    /**
     * Get a WHT summary across all active revenue sources for a period.
     *
     * @return Collection<int, array{source_name: string, revenue_type: string, wht_rate: float, gl_account: string}>
     */
    public function getWhtSummary(): Collection
    {
        return RevenueSource::query()
            ->active()
            ->where('wht_applicable', true)
            ->with('glAccount', 'whtAccount')
            ->get()
            ->map(fn (RevenueSource $s): array => [
                'source_name' => $s->source_name,
                'revenue_type' => $s->revenue_type,
                'wht_rate' => (float) $s->wht_rate,
                'gl_account' => $s->glAccount->account_code.' – '.$s->glAccount->account_name,
                'wht_account' => $s->whtAccount
                    ? $s->whtAccount->account_code.' – '.$s->whtAccount->account_name
                    : '—',
            ]);
    }

    // ─── Budget Management (FR-RE-020–024) ──────────────────────

    /**
     * Get budget variance report for a fiscal year.
     *
     * @return Collection<int, array{budget_code: string, budget_name: string, gl_account: string, approved: float, actual: float, variance: float, variance_pct: float, over_threshold: bool}>
     */
    public function getBudgetVarianceReport(int $fiscalYear): Collection
    {
        return Budget::query()
            ->forYear($fiscalYear)
            ->with('glAccount')
            ->get()
            ->map(fn (Budget $b): array => [
                'budget_code' => $b->budget_code,
                'budget_name' => $b->budget_name,
                'gl_account' => $b->glAccount->account_code.' – '.$b->glAccount->account_name,
                'cost_centre' => $b->cost_centre_code ?? '—',
                'approved' => (float) $b->approved_amount,
                'actual' => (float) $b->actual_amount,
                'variance' => $b->variance,
                'variance_pct' => $b->variance_percentage,
                'remaining' => $b->remaining,
                'over_threshold' => $b->isOverThreshold(),
            ]);
    }

    /**
     * Check if an expense can proceed against the linked budget.
     *
     * @return array{allowed: bool, reason: string|null, remaining: float}
     */
    public function checkBudgetAvailability(int $budgetId, float $amount): array
    {
        $budget = Budget::findOrFail($budgetId);

        if (! in_array($budget->status, [Budget::STATUS_APPROVED, Budget::STATUS_ACTIVE])) {
            return [
                'allowed' => false,
                'reason' => "Budget '{$budget->budget_code}' is not active/approved.",
                'remaining' => 0.0,
            ];
        }

        if ($budget->enforce_budget && $budget->wouldExceedBudget($amount)) {
            return [
                'allowed' => false,
                'reason' => 'Amount UGX '.number_format($amount).' would exceed budget. Remaining: UGX '.number_format($budget->remaining),
                'remaining' => $budget->remaining,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'remaining' => $budget->remaining,
        ];
    }

    // ─── Expense Claims (FR-RE-020–024) ─────────────────────────

    /**
     * Generate a unique claim number.
     */
    public function generateClaimNumber(): string
    {
        $year = now()->format('Y');
        $latest = ExpenseClaim::where('claim_number', 'like', "EXP-{$year}-%")
            ->orderByDesc('claim_number')
            ->value('claim_number');

        $sequence = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $sequence = ((int) end($parts)) + 1;
        }

        return 'EXP-'.$year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Expense claim summary by category.
     *
     * @return Collection<string, array{category: string, count: int, total_claimed: float, total_approved: float, total_paid: float}>
     */
    public function getExpenseSummaryByCategory(?int $fiscalYear = null): Collection
    {
        $query = ExpenseClaim::query();

        if ($fiscalYear) {
            $query->whereYear('expense_date', $fiscalYear);
        }

        return $query->get()
            ->groupBy('category')
            ->map(fn (Collection $claims, string $category): array => [
                'category' => ExpenseClaim::CATEGORIES[$category] ?? $category,
                'count' => $claims->count(),
                'total_claimed' => round($claims->sum('claimed_amount'), 2),
                'total_approved' => round($claims->where('status', '!=', ExpenseClaim::STATUS_REJECTED)->sum('approved_amount'), 2),
                'total_paid' => round($claims->where('status', ExpenseClaim::STATUS_PAID)->sum('approved_amount'), 2),
            ]);
    }

    // ─── Investment Portfolio (FR-RE-030–034) ───────────────────

    /**
     * Get portfolio summary with performance metrics.
     *
     * @return array{total_invested: float, total_current_value: float, total_accrued_income: float, unrealised_gain_loss: float, portfolio_roi: float, by_type: Collection}
     */
    public function getPortfolioSummary(): array
    {
        $investments = Investment::query()->active()->get();

        $totalInvested = $investments->sum('purchase_price');
        $totalCurrentValue = $investments->sum('current_value');
        $totalAccruedIncome = $investments->sum('accrued_income');
        $unrealisedGainLoss = round($totalCurrentValue - $totalInvested, 2);
        $portfolioRoi = $totalInvested > 0
            ? round(($unrealisedGainLoss / $totalInvested) * 100, 2)
            : 0.0;

        $byType = $investments->groupBy('investment_type')
            ->map(fn (Collection $group, string $type): array => [
                'type' => Investment::TYPES[$type] ?? $type,
                'count' => $group->count(),
                'total_invested' => round($group->sum('purchase_price'), 2),
                'current_value' => round($group->sum('current_value'), 2),
                'accrued_income' => round($group->sum('accrued_income'), 2),
            ]);

        return [
            'total_invested' => round($totalInvested, 2),
            'total_current_value' => round($totalCurrentValue, 2),
            'total_accrued_income' => round($totalAccruedIncome, 2),
            'unrealised_gain_loss' => $unrealisedGainLoss,
            'portfolio_roi' => $portfolioRoi,
            'by_type' => $byType,
        ];
    }

    /**
     * Get investments maturing within a number of days.
     *
     * @return Collection<int, Investment>
     */
    public function getUpcomingMaturities(int $withinDays = 30): Collection
    {
        return Investment::query()
            ->active()
            ->whereNotNull('maturity_date')
            ->whereBetween('maturity_date', [now(), now()->addDays($withinDays)])
            ->orderBy('maturity_date')
            ->get();
    }
}
