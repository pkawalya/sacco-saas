<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * KYC screening record for PEP/sanctions/ID verification (Sprint 4.4).
 *
 * @property int $id
 * @property string $screening_ref
 * @property int $member_id
 * @property string $screening_type
 * @property int $kyc_tier
 * @property string $result
 * @property float|null $match_score
 */
class KycScreening extends Model
{
    public const TYPE_PEP = 'pep';

    public const TYPE_SANCTIONS = 'sanctions';

    public const TYPE_ADVERSE_MEDIA = 'adverse_media';

    public const TYPE_ID_VERIFICATION = 'id_verification';

    public const TYPES = [
        self::TYPE_PEP => 'PEP Screening',
        self::TYPE_SANCTIONS => 'Sanctions Screening',
        self::TYPE_ADVERSE_MEDIA => 'Adverse Media',
        self::TYPE_ID_VERIFICATION => 'ID Verification',
    ];

    public const RESULT_PENDING = 'pending';

    public const RESULT_CLEAR = 'clear';

    public const RESULT_MATCH = 'match';

    public const RESULT_REVIEW = 'review_needed';

    public const RESULT_FAILED = 'failed';

    public const RESULTS = [
        self::RESULT_PENDING => 'Pending',
        self::RESULT_CLEAR => 'Clear',
        self::RESULT_MATCH => 'Match Found',
        self::RESULT_REVIEW => 'Review Needed',
        self::RESULT_FAILED => 'Failed',
    ];

    protected $fillable = [
        'screening_ref',
        'member_id',
        'member_name',
        'screening_type',
        'kyc_tier',
        'result',
        'match_score',
        'match_details',
        'data_source',
        'verification_id',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_score' => 'decimal:2',
            'match_details' => 'array',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isHighRisk(): bool
    {
        return in_array($this->result, [self::RESULT_MATCH, self::RESULT_REVIEW])
            && $this->match_score !== null
            && (float) $this->match_score >= 70;
    }
}
