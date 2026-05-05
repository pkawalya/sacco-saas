<?php

namespace App\Services\Tenant;

use App\Models\Tenant\EclComputation;
use App\Models\Tenant\EclStaging;

/**
 * IFRS 9 ECL computation service (Sprint 4.1).
 */
class EclService
{
    /**
     * Stage a loan for ECL computation.
     *
     * @param  array<string, mixed>  $data
     */
    public function stageLoan(array $data): EclStaging
    {
        $dpd = $data['dpd'] ?? 0;
        $stage = EclStaging::determineStage($dpd);

        $pd = $data['pd'] ?? $this->defaultPd($dpd);
        $lgd = $data['lgd'] ?? 0.45;
        $ead = $data['ead'] ?? 0;

        $ecl = round($pd * $lgd * $ead, 2);

        return EclStaging::updateOrCreate(
            [
                'loan_id' => $data['loan_id'],
                'computation_period' => $data['computation_period'] ?? now()->format('Y-m'),
            ],
            [
                'loan_number' => $data['loan_number'] ?? '',
                'stage' => $stage,
                'dpd' => $dpd,
                'pd' => $pd,
                'lgd' => $lgd,
                'ead' => $ead,
                'ecl_amount' => $ecl,
            ]
        );
    }

    /**
     * Run ECL computation for a period.
     */
    public function runComputation(string $period): EclComputation
    {
        $stagings = EclStaging::forPeriod($period)->get();

        $computation = EclComputation::updateOrCreate(
            ['computation_period' => $period],
            [
                'computation_date' => now()->toDateString(),
                'total_ead' => $stagings->sum('ead'),
                'total_ecl' => $stagings->sum('ecl_amount'),
                'provision_amount' => $stagings->sum('ecl_amount'),
                'stage_1_count' => $stagings->where('stage', 1)->count(),
                'stage_1_ecl' => $stagings->where('stage', 1)->sum('ecl_amount'),
                'stage_2_count' => $stagings->where('stage', 2)->count(),
                'stage_2_ecl' => $stagings->where('stage', 2)->sum('ecl_amount'),
                'stage_3_count' => $stagings->where('stage', 3)->count(),
                'stage_3_ecl' => $stagings->where('stage', 3)->sum('ecl_amount'),
                'coverage_ratio' => $stagings->sum('ead') > 0
                    ? round($stagings->sum('ecl_amount') / $stagings->sum('ead'), 4)
                    : 0,
            ]
        );

        return $computation;
    }

    /**
     * Default PD based on DPD (simplified model).
     */
    private function defaultPd(int $dpd): float
    {
        return match (true) {
            $dpd === 0 => 0.01,
            $dpd <= 30 => 0.05,
            $dpd <= 60 => 0.15,
            $dpd <= 90 => 0.35,
            $dpd <= 180 => 0.65,
            default => 0.95,
        };
    }
}
