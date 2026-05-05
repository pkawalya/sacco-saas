<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * CRB submission record (FR-RC-020).
 *
 * @property int $id
 * @property string $submission_ref
 * @property string $submission_date
 * @property int $record_count
 * @property int $positive_records
 * @property int $negative_records
 * @property string $status
 */
class CrbSubmission extends Model
{
    protected $table = 'crb_submissions';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'submission_ref',
        'submission_date',
        'period',
        'period_start',
        'period_end',
        'record_count',
        'positive_records',
        'negative_records',
        'status',
        'rejection_reason',
        'crb_name',
        'crb_reference',
        'submitted_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function getNplRatioAttribute(): float
    {
        if ($this->record_count === 0) {
            return 0.0;
        }

        return round(($this->negative_records / $this->record_count) * 100, 2);
    }
}
