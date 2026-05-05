<?php

namespace App\Services\Tenant;

use App\Models\Tenant\CrbInquiry;
use Illuminate\Support\Str;

/**
 * CRB integration service (Sprint 4.3).
 */
class CrbIntegrationService
{
    /**
     * Submit a credit score inquiry.
     *
     * @param  array<string, mixed>  $data
     */
    public function inquire(array $data): CrbInquiry
    {
        $ref = 'CRB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        $inquiry = CrbInquiry::create(array_merge($data, [
            'inquiry_ref' => $ref,
            'inquiry_date' => now(),
            'status' => CrbInquiry::STATUS_PENDING,
        ]));

        // Simulate API call — in production would call TransUnion/Metropol API
        $score = $data['credit_score'] ?? rand(200, 900);
        $inquiry->update([
            'credit_score' => $score,
            'risk_grade' => CrbInquiry::gradeFromScore($score),
            'status' => CrbInquiry::STATUS_COMPLETED,
        ]);

        return $inquiry->fresh();
    }

    /**
     * Get member's credit history.
     */
    public function getMemberHistory(int $memberId): array
    {
        $inquiries = CrbInquiry::where('member_id', $memberId)
            ->orderByDesc('inquiry_date')
            ->get();

        $latest = $inquiries->first();

        return [
            'total_inquiries' => $inquiries->count(),
            'latest_score' => $latest?->credit_score,
            'latest_grade' => $latest?->risk_grade,
            'latest_date' => $latest?->inquiry_date?->toDateString(),
            'history' => $inquiries->map(fn (CrbInquiry $i): array => [
                'ref' => $i->inquiry_ref,
                'score' => $i->credit_score,
                'grade' => $i->risk_grade,
                'date' => $i->inquiry_date?->toDateString(),
            ])->all(),
        ];
    }
}
