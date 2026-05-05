<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * CRB credit score inquiry (Sprint 4.3).
 *
 * @property int $id
 * @property string $inquiry_ref
 * @property int $member_id
 * @property int|null $credit_score
 * @property string|null $risk_grade
 * @property string $status
 */
class CrbInquiry extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_EXPIRED => 'Expired',
    ];

    public const RISK_GRADES = [
        'AA' => 'Excellent',
        'A' => 'Good',
        'B' => 'Fair',
        'C' => 'Poor',
        'D' => 'Very Poor',
        'HR' => 'High Risk',
    ];

    protected $fillable = [
        'inquiry_ref',
        'member_id',
        'member_name',
        'national_id',
        'crb_name',
        'inquiry_type',
        'inquiry_date',
        'credit_score',
        'risk_grade',
        'report_data',
        'total_exposure',
        'active_facilities',
        'npls',
        'status',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inquiry_date' => 'datetime',
            'report_data' => 'array',
            'total_exposure' => 'decimal:2',
        ];
    }

    /**
     * Derive risk grade from credit score (Uganda CRB scale).
     */
    public static function gradeFromScore(int $score): string
    {
        return match (true) {
            $score >= 800 => 'AA',
            $score >= 650 => 'A',
            $score >= 500 => 'B',
            $score >= 350 => 'C',
            $score >= 200 => 'D',
            default => 'HR',
        };
    }
}
