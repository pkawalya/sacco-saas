<?php

namespace App\Services\Tenant;

use App\Models\Tenant\KycScreening;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Enhanced KYC service with tiered screening (Sprint 4.4).
 */
class KycService
{
    /**
     * Run a screening for a member.
     *
     * @param  array<string, mixed>  $data
     */
    public function runScreening(array $data): KycScreening
    {
        $ref = 'KYC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        return KycScreening::create(array_merge($data, [
            'screening_ref' => $ref,
            'result' => $data['result'] ?? KycScreening::RESULT_PENDING,
        ]));
    }

    /**
     * Get all screenings for a member.
     *
     * @return Collection<int, KycScreening>
     */
    public function getMemberScreenings(int $memberId): Collection
    {
        return KycScreening::where('member_id', $memberId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Compute KYC completeness for a member.
     *
     * @return array{tier: int, completed: int, total: int, score: float, has_pep: bool, has_sanctions: bool, has_id_verification: bool}
     */
    public function getKycCompleteness(int $memberId, int $requiredTier = 1): array
    {
        $screenings = $this->getMemberScreenings($memberId);

        $requiredTypes = match ($requiredTier) {
            1 => [KycScreening::TYPE_ID_VERIFICATION],
            2 => [KycScreening::TYPE_ID_VERIFICATION, KycScreening::TYPE_PEP, KycScreening::TYPE_SANCTIONS],
            3 => [KycScreening::TYPE_ID_VERIFICATION, KycScreening::TYPE_PEP, KycScreening::TYPE_SANCTIONS, KycScreening::TYPE_ADVERSE_MEDIA],
            default => [KycScreening::TYPE_ID_VERIFICATION],
        };

        $cleared = $screenings->where('result', KycScreening::RESULT_CLEAR);
        $completed = $cleared->whereIn('screening_type', $requiredTypes)->unique('screening_type')->count();

        return [
            'tier' => $requiredTier,
            'completed' => $completed,
            'total' => count($requiredTypes),
            'score' => count($requiredTypes) > 0 ? round(($completed / count($requiredTypes)) * 100, 1) : 0,
            'has_pep' => $cleared->contains('screening_type', KycScreening::TYPE_PEP),
            'has_sanctions' => $cleared->contains('screening_type', KycScreening::TYPE_SANCTIONS),
            'has_id_verification' => $cleared->contains('screening_type', KycScreening::TYPE_ID_VERIFICATION),
        ];
    }

    /**
     * Get high-risk members needing review.
     *
     * @return Collection<int, KycScreening>
     */
    public function getHighRiskScreenings(): Collection
    {
        return KycScreening::whereIn('result', [KycScreening::RESULT_MATCH, KycScreening::RESULT_REVIEW])
            ->where('match_score', '>=', 70)
            ->whereNull('reviewed_at')
            ->orderByDesc('match_score')
            ->get();
    }
}
