<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AmlAlert;
use App\Models\Tenant\CrbSubmission;
use App\Models\Tenant\RegulatoryReturn;
use App\Models\Tenant\StrReport;
use App\Models\Tenant\TaxCalendar;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Regulatory Compliance service.
 *
 * FR-RC-001–003: Prudential returns, deadlines, filing
 * FR-RC-010–013: AML monitoring, alert queue, STR/CTR
 * FR-RC-020–022: CRB submission, PAYE, tax calendar
 */
class RegulatoryComplianceService
{
    // ─── Returns (FR-RC-001–003) ────────────────────────────────

    /**
     * Generate a regulatory return for a period.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateReturn(array $data): RegulatoryReturn
    {
        $code = 'RR-'.Str::upper($data['return_type'] ?? 'GEN').'-'.($data['fiscal_year'] ?? now()->year).'-'.str_pad((string) (($data['period_number'] ?? 0) + 1), 2, '0', STR_PAD_LEFT);

        return RegulatoryReturn::create(array_merge($data, [
            'return_code' => $code,
            'status' => RegulatoryReturn::STATUS_PENDING,
        ]));
    }

    /**
     * Get upcoming returns due within N days (FR-RC-002).
     *
     * @return Collection<int, RegulatoryReturn>
     */
    public function getUpcomingReturns(int $withinDays = 14): Collection
    {
        return RegulatoryReturn::query()
            ->dueSoon($withinDays)
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Flag overdue returns.
     */
    public function flagOverdueReturns(): int
    {
        return RegulatoryReturn::query()
            ->where('status', RegulatoryReturn::STATUS_PENDING)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => RegulatoryReturn::STATUS_OVERDUE]);
    }

    /**
     * Filing compliance dashboard data.
     *
     * @return array{total: int, pending: int, submitted: int, accepted: int, overdue: int, compliance_rate: float}
     */
    public function getFilingDashboard(int $fiscalYear): array
    {
        $returns = RegulatoryReturn::query()
            ->where('fiscal_year', $fiscalYear)
            ->get();

        $total = $returns->count();
        $submitted = $returns->whereIn('status', [
            RegulatoryReturn::STATUS_SUBMITTED,
            RegulatoryReturn::STATUS_ACCEPTED,
        ])->count();

        return [
            'total' => $total,
            'pending' => $returns->where('status', RegulatoryReturn::STATUS_PENDING)->count(),
            'submitted' => $submitted,
            'accepted' => $returns->where('status', RegulatoryReturn::STATUS_ACCEPTED)->count(),
            'overdue' => $returns->where('status', RegulatoryReturn::STATUS_OVERDUE)->count(),
            'compliance_rate' => $total > 0 ? round(($submitted / $total) * 100, 2) : 0.0,
        ];
    }

    // ─── AML Monitoring (FR-RC-010–013) ─────────────────────────

    /**
     * Generate an AML alert from transaction monitoring.
     *
     * @param  array<string, mixed>  $data
     */
    public function raiseAmlAlert(array $data): AmlAlert
    {
        $alertId = 'AML-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        return AmlAlert::create(array_merge($data, [
            'alert_id' => $alertId,
            'status' => AmlAlert::STATUS_NEW,
        ]));
    }

    /**
     * Get the AML alert queue (unresolved, sorted by severity).
     *
     * @return Collection<int, AmlAlert>
     */
    public function getAlertQueue(): Collection
    {
        $severityOrder = [
            AmlAlert::SEVERITY_CRITICAL => 1,
            AmlAlert::SEVERITY_HIGH => 2,
            AmlAlert::SEVERITY_MEDIUM => 3,
            AmlAlert::SEVERITY_LOW => 4,
        ];

        return AmlAlert::query()
            ->unresolved()
            ->get()
            ->sortBy(fn (AmlAlert $a): int => $severityOrder[$a->severity] ?? 99);
    }

    /**
     * Generate an STR/CTR from an AML alert (FR-RC-012–013).
     *
     * @param  array<string, mixed>  $overrides
     */
    public function generateStr(int $alertId, array $overrides = []): StrReport
    {
        $alert = AmlAlert::findOrFail($alertId);

        $str = StrReport::create(array_merge([
            'str_reference' => 'STR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'aml_alert_id' => $alert->id,
            'member_id' => $alert->member_id,
            'member_name' => $alert->member_name,
            'amount' => $alert->transaction_amount ?? 0,
            'report_type' => StrReport::TYPE_STR,
            'status' => StrReport::STATUS_DRAFT,
            'suspicious_activity_description' => 'Alert: '.($alert->rule_triggered ?? '').' — '.($alert->review_notes ?? 'See AML alert'),
        ], $overrides));

        // Mark alert as STR filed
        $alert->update(['status' => AmlAlert::STATUS_STR_FILED]);

        return $str;
    }

    /**
     * AML summary dashboard.
     *
     * @return array{total_alerts: int, unresolved: int, high_risk: int, str_filed: int, by_rule: Collection}
     */
    public function getAmlDashboard(): array
    {
        $alerts = AmlAlert::all();

        return [
            'total_alerts' => $alerts->count(),
            'unresolved' => $alerts->whereNotIn('status', [AmlAlert::STATUS_CLEARED, AmlAlert::STATUS_STR_FILED])->count(),
            'high_risk' => $alerts->whereIn('severity', [AmlAlert::SEVERITY_HIGH, AmlAlert::SEVERITY_CRITICAL])->count(),
            'str_filed' => $alerts->where('status', AmlAlert::STATUS_STR_FILED)->count(),
            'by_rule' => $alerts->groupBy('rule_triggered')->map->count(),
        ];
    }

    // ─── CRB (FR-RC-020) ──────────────────────────────────────

    /**
     * Create a CRB submission record.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCrbSubmission(array $data): CrbSubmission
    {
        $ref = 'CRB-'.now()->format('Ym').'-'.str_pad((string) (CrbSubmission::count() + 1), 3, '0', STR_PAD_LEFT);

        return CrbSubmission::create(array_merge($data, [
            'submission_ref' => $ref,
            'status' => CrbSubmission::STATUS_PENDING,
        ]));
    }

    // ─── Tax Calendar (FR-RC-021–022) ──────────────────────────

    /**
     * Get upcoming tax obligations.
     *
     * @return Collection<int, TaxCalendar>
     */
    public function getUpcomingTaxObligations(int $withinDays = 30): Collection
    {
        return TaxCalendar::query()
            ->dueSoon($withinDays)
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Flag overdue tax obligations.
     */
    public function flagOverdueTaxes(): int
    {
        return TaxCalendar::query()
            ->whereNotIn('filing_status', [TaxCalendar::STATUS_FILED, TaxCalendar::STATUS_PAID])
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['filing_status' => TaxCalendar::STATUS_OVERDUE]);
    }

    /**
     * Tax compliance summary for a fiscal year.
     *
     * @return array{total_computed: float, total_paid: float, total_penalty: float, total_balance: float, by_type: Collection, overdue_count: int}
     */
    public function getTaxSummary(int $fiscalYear): array
    {
        $entries = TaxCalendar::query()
            ->where('fiscal_year', $fiscalYear)
            ->get();

        return [
            'total_computed' => round($entries->sum('computed_amount'), 2),
            'total_paid' => round($entries->sum('paid_amount'), 2),
            'total_penalty' => round($entries->sum('penalty_amount'), 2),
            'total_balance' => round($entries->sum(fn (TaxCalendar $t): float => $t->balance_due), 2),
            'by_type' => $entries->groupBy('tax_type')->map(fn (Collection $group): array => [
                'count' => $group->count(),
                'computed' => round($group->sum('computed_amount'), 2),
                'paid' => round($group->sum('paid_amount'), 2),
            ]),
            'overdue_count' => $entries->where('filing_status', TaxCalendar::STATUS_OVERDUE)->count(),
        ];
    }
}
