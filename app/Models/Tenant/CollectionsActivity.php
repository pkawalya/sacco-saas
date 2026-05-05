<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Collections activity log entry (FR-CE-010).
 *
 * @property int $id
 * @property int $worklist_id
 * @property int $loan_id
 * @property string $loan_number
 * @property string $activity_type
 * @property string $description
 * @property string|null $outcome
 * @property int|null $officer_id
 * @property string|null $officer_name
 */
class CollectionsActivity extends Model
{
    protected $table = 'collections_activities';

    // ─── Activity Types ─────────────────────────────────────────
    public const TYPE_CALL = 'call';

    public const TYPE_VISIT = 'visit';

    public const TYPE_SMS = 'sms';

    public const TYPE_EMAIL = 'email';

    public const TYPE_PTP = 'ptp';

    public const TYPE_LETTER = 'letter';

    public const TYPE_LEGAL = 'legal';

    public const TYPE_NOTE = 'note';

    public const TYPES = [
        self::TYPE_CALL => 'Phone Call',
        self::TYPE_VISIT => 'Field Visit',
        self::TYPE_SMS => 'SMS',
        self::TYPE_EMAIL => 'Email',
        self::TYPE_PTP => 'Promise to Pay',
        self::TYPE_LETTER => 'Letter',
        self::TYPE_LEGAL => 'Legal Action',
        self::TYPE_NOTE => 'Note',
    ];

    // ─── Outcome Constants ──────────────────────────────────────
    public const OUTCOME_CONTACTED = 'contacted';

    public const OUTCOME_NOT_REACHABLE = 'not_reachable';

    public const OUTCOME_PROMISE_MADE = 'promise_made';

    public const OUTCOME_REFUSED = 'refused';

    public const OUTCOME_PARTIAL_PAYMENT = 'partial_payment';

    public const OUTCOME_ARRANGEMENT = 'arrangement';

    public const OUTCOMES = [
        self::OUTCOME_CONTACTED => 'Contacted',
        self::OUTCOME_NOT_REACHABLE => 'Not Reachable',
        self::OUTCOME_PROMISE_MADE => 'Promise Made',
        self::OUTCOME_REFUSED => 'Refused to Pay',
        self::OUTCOME_PARTIAL_PAYMENT => 'Partial Payment',
        self::OUTCOME_ARRANGEMENT => 'Arrangement Made',
    ];

    protected $fillable = [
        'worklist_id',
        'loan_id',
        'loan_number',
        'activity_type',
        'description',
        'outcome',
        'officer_id',
        'officer_name',
        'contact_number',
        'contact_time',
        'duration_minutes',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_time' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function worklist(): BelongsTo
    {
        return $this->belongsTo(CollectionsWorklist::class, 'worklist_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('activity_type', $type);
    }
}
