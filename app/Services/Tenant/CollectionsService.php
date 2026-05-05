<?php

namespace App\Services\Tenant;

use App\Models\Tenant\CollectionsActivity;
use App\Models\Tenant\CollectionsWorklist;
use App\Models\Tenant\DemandLetter;
use App\Models\Tenant\PtpRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Collections Engine service.
 *
 * FR-CE-001: Daily delinquency reclassification
 * FR-CE-002: Daily penalty computation
 * FR-CE-003: Worklist per officer
 * FR-CE-004: Auto-escalation
 * FR-CE-010: Activity logging
 * FR-CE-011: PTP with broken promise flag
 * FR-CE-012: PTP performance metrics
 * FR-CE-013: Demand letters
 * FR-CE-030–031: PAR aging, collector scorecard
 */
class CollectionsService
{
    // ─── EOD Processing (FR-CE-001, FR-CE-002, FR-CE-004) ──────

    /**
     * Run daily EOD processing: reclassify, accrue penalties, auto-escalate.
     *
     * @return array{reclassified: int, penalties_accrued: int, escalated: int, ptps_broken: int}
     */
    public function runDailyEod(): array
    {
        $stats = ['reclassified' => 0, 'penalties_accrued' => 0, 'escalated' => 0, 'ptps_broken' => 0];

        $entries = CollectionsWorklist::query()->active()->get();

        foreach ($entries as $entry) {
            // FR-CE-001: Reclassify bucket
            $oldBucket = $entry->delinquency_bucket;
            $entry->reclassify();
            if ($entry->delinquency_bucket !== $oldBucket) {
                $stats['reclassified']++;
            }

            // FR-CE-002: Accrue daily penalty
            if ($entry->computeDailyPenalty() > 0) {
                $entry->accruePenalty();
                $stats['penalties_accrued']++;
            }

            // FR-CE-004: Auto-escalation
            if ($entry->autoEscalate()) {
                $stats['escalated']++;
            }
        }

        // FR-CE-011: Flag broken PTPs
        $stats['ptps_broken'] = $this->flagBrokenPtps();

        return $stats;
    }

    // ─── Worklist (FR-CE-003) ──────────────────────────────────

    /**
     * Get worklist for a specific officer, sorted by DPD descending.
     *
     * @return Collection<int, CollectionsWorklist>
     */
    public function getOfficerWorklist(int $officerId): Collection
    {
        return CollectionsWorklist::query()
            ->active()
            ->forOfficer($officerId)
            ->orderByDesc('dpd')
            ->get();
    }

    /**
     * Get worklist filtered by tier.
     *
     * @return Collection<int, CollectionsWorklist>
     */
    public function getWorklistByTier(int $tier): Collection
    {
        return CollectionsWorklist::query()
            ->active()
            ->inTier($tier)
            ->orderByDesc('dpd')
            ->get();
    }

    // ─── Activity Logging (FR-CE-010) ──────────────────────────

    /**
     * Log a collections activity against a worklist entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function logActivity(int $worklistId, array $data): CollectionsActivity
    {
        $worklist = CollectionsWorklist::findOrFail($worklistId);

        return CollectionsActivity::create(array_merge($data, [
            'worklist_id' => $worklistId,
            'loan_id' => $worklist->loan_id,
            'loan_number' => $worklist->loan_number,
        ]));
    }

    // ─── PTP Management (FR-CE-011, FR-CE-012) ─────────────────

    /**
     * Capture a Promise to Pay.
     *
     * @param  array<string, mixed>  $data
     */
    public function capturePtp(int $worklistId, array $data): PtpRecord
    {
        $worklist = CollectionsWorklist::findOrFail($worklistId);

        $ptp = PtpRecord::create(array_merge($data, [
            'worklist_id' => $worklistId,
            'loan_id' => $worklist->loan_id,
            'loan_number' => $worklist->loan_number,
            'status' => PtpRecord::STATUS_PENDING,
            'is_broken' => false,
        ]));

        // Also log as an activity
        $this->logActivity($worklistId, [
            'activity_type' => CollectionsActivity::TYPE_PTP,
            'description' => 'PTP captured: UGX '.number_format((float) $data['promised_amount']).' due '.$data['promised_date'],
            'outcome' => CollectionsActivity::OUTCOME_PROMISE_MADE,
            'officer_id' => $data['captured_by'] ?? null,
            'officer_name' => $data['officer_name'] ?? null,
        ]);

        return $ptp;
    }

    /**
     * Flag all overdue PTPs as broken (FR-CE-011).
     */
    public function flagBrokenPtps(): int
    {
        $overduePtps = PtpRecord::query()
            ->pending()
            ->whereDate('promised_date', '<', now()->toDateString())
            ->get();

        foreach ($overduePtps as $ptp) {
            $ptp->flagAsBroken();
        }

        return $overduePtps->count();
    }

    /**
     * PTP performance metrics per officer (FR-CE-012).
     *
     * @return Collection<int, array{officer_name: string, total: int, kept: int, broken: int, partial: int, pending: int, kept_rate: float}>
     */
    public function getPtpPerformanceByOfficer(): Collection
    {
        return PtpRecord::query()
            ->get()
            ->groupBy('officer_name')
            ->map(function (Collection $ptps, string $officer): array {
                $total = $ptps->count();
                $kept = $ptps->where('status', PtpRecord::STATUS_KEPT)->count();
                $broken = $ptps->where('status', PtpRecord::STATUS_BROKEN)->count();
                $partial = $ptps->where('status', PtpRecord::STATUS_PARTIAL)->count();
                $pending = $ptps->where('status', PtpRecord::STATUS_PENDING)->count();

                return [
                    'officer_name' => $officer,
                    'total' => $total,
                    'kept' => $kept,
                    'broken' => $broken,
                    'partial' => $partial,
                    'pending' => $pending,
                    'kept_rate' => $total > 0 ? round(($kept / $total) * 100, 2) : 0.0,
                ];
            })
            ->values();
    }

    // ─── Demand Letters (FR-CE-013) ─────────────────────────────

    /**
     * Generate a demand letter for a worklist entry.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function generateDemandLetter(int $worklistId, string $letterType, array $overrides = []): DemandLetter
    {
        $worklist = CollectionsWorklist::findOrFail($worklistId);

        $letter = DemandLetter::create(array_merge([
            'worklist_id' => $worklistId,
            'loan_id' => $worklist->loan_id,
            'loan_number' => $worklist->loan_number,
            'letter_type' => $letterType,
            'reference_number' => 'DL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'recipient_name' => $worklist->member_name,
            'recipient_type' => 'borrower',
            'delivery_method' => 'print',
            'status' => DemandLetter::STATUS_DRAFT,
        ], $overrides));

        // Log activity
        $this->logActivity($worklistId, [
            'activity_type' => CollectionsActivity::TYPE_LETTER,
            'description' => DemandLetter::LETTER_TYPES[$letterType].' generated: '.$letter->reference_number,
        ]);

        return $letter;
    }

    // ─── PAR Aging Report (FR-CE-030) ──────────────────────────

    /**
     * Generate PAR (Portfolio at Risk) aging report.
     *
     * @return array{buckets: Collection, total_par: float, total_outstanding: float, par_ratio: float}
     */
    public function getParAgingReport(): array
    {
        $entries = CollectionsWorklist::query()->active()->get();

        $totalOutstanding = $entries->sum('outstanding_balance');

        $buckets = collect(CollectionsWorklist::BUCKETS)->map(function (string $label, string $bucket) use ($entries): array {
            $inBucket = $entries->where('delinquency_bucket', $bucket);

            return [
                'bucket' => $label,
                'count' => $inBucket->count(),
                'arrears' => round($inBucket->sum('arrears_amount'), 2),
                'outstanding' => round($inBucket->sum('outstanding_balance'), 2),
                'penalty' => round($inBucket->sum('accrued_penalty'), 2),
            ];
        })->values();

        $totalPar = $entries->where('delinquency_bucket', '!=', CollectionsWorklist::BUCKET_CURRENT)
            ->sum('outstanding_balance');

        return [
            'buckets' => $buckets,
            'total_par' => round($totalPar, 2),
            'total_outstanding' => round($totalOutstanding, 2),
            'par_ratio' => $totalOutstanding > 0
                ? round(($totalPar / $totalOutstanding) * 100, 2)
                : 0.0,
        ];
    }

    // ─── Collector Scorecard (FR-CE-031) ───────────────────────

    /**
     * Generate collector scorecard.
     *
     * @return Collection<int, array{officer_name: string, assigned: int, resolved: int, total_arrears: float, total_collected: float, collection_rate: float, avg_dpd: float}>
     */
    public function getCollectorScorecard(): Collection
    {
        $active = CollectionsWorklist::query()->active()->get();
        $resolved = CollectionsWorklist::query()->where('status', CollectionsWorklist::STATUS_RESOLVED)->get();

        $allEntries = $active->merge($resolved);

        return $allEntries
            ->groupBy('officer_name')
            ->filter(fn (?string $key): bool => $key !== null && $key !== '')
            ->map(function (Collection $entries, string $officer): array {
                $officerActive = $entries->where('status', CollectionsWorklist::STATUS_ACTIVE);
                $officerResolved = $entries->where('status', CollectionsWorklist::STATUS_RESOLVED);
                $totalArrears = $entries->sum('arrears_amount');

                return [
                    'officer_name' => $officer,
                    'assigned' => $officerActive->count(),
                    'resolved' => $officerResolved->count(),
                    'total_arrears' => round($totalArrears, 2),
                    'total_collected' => round($officerResolved->sum('arrears_amount'), 2),
                    'collection_rate' => $entries->count() > 0
                        ? round(($officerResolved->count() / $entries->count()) * 100, 2)
                        : 0.0,
                    'avg_dpd' => $officerActive->count() > 0
                        ? round($officerActive->avg('dpd'), 0)
                        : 0,
                ];
            })
            ->values();
    }
}
