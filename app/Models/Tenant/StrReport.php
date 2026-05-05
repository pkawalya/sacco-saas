<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suspicious Transaction Report / Currency Transaction Report (FR-RC-012–013).
 *
 * @property int $id
 * @property string $str_reference
 * @property string $member_name
 * @property float $amount
 * @property string $report_type
 * @property string $status
 */
class StrReport extends Model
{
    protected $table = 'str_reports';

    public const TYPE_STR = 'str';

    public const TYPE_CTR = 'ctr';

    public const REPORT_TYPES = [
        self::TYPE_STR => 'Suspicious Transaction Report',
        self::TYPE_CTR => 'Currency Transaction Report',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RETURNED = 'returned';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_ACKNOWLEDGED => 'Acknowledged',
        self::STATUS_RETURNED => 'Returned',
    ];

    protected $fillable = [
        'str_reference',
        'aml_alert_id',
        'member_id',
        'member_name',
        'amount',
        'transaction_type',
        'suspicious_activity_description',
        'report_type',
        'status',
        'filed_date',
        'fia_reference',
        'prepared_by',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'filed_date' => 'date',
        ];
    }

    public function amlAlert(): BelongsTo
    {
        return $this->belongsTo(AmlAlert::class, 'aml_alert_id');
    }

    public function submit(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'filed_date' => now()->toDateString(),
            'prepared_by' => $userId,
        ]);
    }
}
