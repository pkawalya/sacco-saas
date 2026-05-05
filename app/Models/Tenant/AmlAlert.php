<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AML transaction monitoring alert (FR-RC-010–011).
 *
 * @property int $id
 * @property string $alert_id
 * @property string $rule_triggered
 * @property string $member_name
 * @property string $severity
 * @property string $status
 * @property int $risk_score
 */
class AmlAlert extends Model
{
    protected $table = 'aml_alerts';

    // ─── Rule Constants ─────────────────────────────────────────
    public const RULE_THRESHOLD = 'threshold_breach';

    public const RULE_RAPID = 'rapid_transactions';

    public const RULE_STRUCTURING = 'structuring';

    public const RULE_PEP = 'pep_match';

    public const RULE_SANCTIONS = 'sanctions_match';

    public const RULE_UNUSUAL = 'unusual_pattern';

    public const RULES = [
        self::RULE_THRESHOLD => 'Threshold Breach',
        self::RULE_RAPID => 'Rapid Transactions',
        self::RULE_STRUCTURING => 'Structuring',
        self::RULE_PEP => 'PEP Match',
        self::RULE_SANCTIONS => 'Sanctions Match',
        self::RULE_UNUSUAL => 'Unusual Pattern',
    ];

    // ─── Severity ───────────────────────────────────────────────
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_LOW => 'Low',
        self::SEVERITY_MEDIUM => 'Medium',
        self::SEVERITY_HIGH => 'High',
        self::SEVERITY_CRITICAL => 'Critical',
    ];

    // ─── Status ─────────────────────────────────────────────────
    public const STATUS_NEW = 'new';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_CLEARED = 'cleared';

    public const STATUS_STR_FILED = 'str_filed';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_ESCALATED => 'Escalated',
        self::STATUS_CLEARED => 'Cleared',
        self::STATUS_STR_FILED => 'STR Filed',
    ];

    protected $fillable = [
        'alert_id',
        'rule_triggered',
        'member_id',
        'member_name',
        'account_number',
        'transaction_amount',
        'cumulative_amount',
        'transaction_reference',
        'severity',
        'is_escalated',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'risk_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_amount' => 'decimal:2',
            'cumulative_amount' => 'decimal:2',
            'is_escalated' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function strReports(): HasMany
    {
        return $this->hasMany(StrReport::class, 'aml_alert_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUnresolved(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_CLEARED, self::STATUS_STR_FILED]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeHighRisk(Builder $query): void
    {
        $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function escalate(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
            'is_escalated' => true,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);
    }

    public function clear(int $userId, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_CLEARED,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }
}
