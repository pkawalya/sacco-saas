<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legal case register for collections legal stage.
 *
 * @property int $id
 * @property string $case_ref
 * @property int $worklist_id
 * @property int $loan_id
 * @property string $loan_number
 * @property string|null $court
 * @property string|null $case_number
 * @property string $filing_date
 * @property string|null $next_hearing_date
 * @property float $claim_amount
 * @property float $legal_costs
 * @property float $recovered_amount
 * @property string $status
 */
class LegalCase extends Model
{
    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_FILED = 'filed';

    public const STATUS_HEARING = 'hearing';

    public const STATUS_JUDGMENT = 'judgment';

    public const STATUS_EXECUTION = 'execution';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_FILED => 'Filed',
        self::STATUS_HEARING => 'Hearing',
        self::STATUS_JUDGMENT => 'Judgment',
        self::STATUS_EXECUTION => 'Execution',
        self::STATUS_SETTLED => 'Settled',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'case_ref',
        'worklist_id',
        'loan_id',
        'loan_number',
        'court',
        'case_number',
        'filing_date',
        'next_hearing_date',
        'claim_amount',
        'legal_costs',
        'recovered_amount',
        'status',
        'advocate_name',
        'advocate_contact',
        'notes',
        'filed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claim_amount' => 'decimal:2',
            'legal_costs' => 'decimal:2',
            'recovered_amount' => 'decimal:2',
            'filing_date' => 'date',
            'next_hearing_date' => 'date',
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
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_SETTLED, self::STATUS_CLOSED]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Recovery rate as percentage.
     */
    public function getRecoveryRateAttribute(): float
    {
        if ((float) $this->claim_amount === 0.0) {
            return 0.0;
        }

        return round(((float) $this->recovered_amount / (float) $this->claim_amount) * 100, 2);
    }
}
