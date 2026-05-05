<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Collections worklist entry for a delinquent loan.
 *
 * FR-CE-001: Delinquency reclassification
 * FR-CE-002: Penalty computation
 * FR-CE-003: Worklist per officer
 * FR-CE-004: Auto-escalation
 *
 * @property int $id
 * @property int $loan_id
 * @property string $loan_number
 * @property string $member_name
 * @property int|null $member_id
 * @property int $dpd
 * @property float $arrears_amount
 * @property float $outstanding_balance
 * @property float $instalment_amount
 * @property string $delinquency_bucket
 * @property int $tier
 * @property int|null $previous_tier
 * @property int|null $officer_id
 * @property string|null $officer_name
 * @property string|null $branch_code
 * @property float $penalty_rate
 * @property float $accrued_penalty
 * @property string $status
 */
class CollectionsWorklist extends Model
{
    protected $table = 'collections_worklist';

    // ─── Bucket Constants (FR-CE-001) ───────────────────────────
    public const BUCKET_CURRENT = 'current';

    public const BUCKET_1_30 = '1-30';

    public const BUCKET_31_60 = '31-60';

    public const BUCKET_61_90 = '61-90';

    public const BUCKET_91_180 = '91-180';

    public const BUCKET_180_PLUS = '180+';

    public const BUCKETS = [
        self::BUCKET_CURRENT => 'Current',
        self::BUCKET_1_30 => '1–30 Days',
        self::BUCKET_31_60 => '31–60 Days',
        self::BUCKET_61_90 => '61–90 Days',
        self::BUCKET_91_180 => '91–180 Days',
        self::BUCKET_180_PLUS => '180+ Days',
    ];

    /** @var array<string, array{min: int, max: int}> */
    public const BUCKET_RANGES = [
        self::BUCKET_CURRENT => ['min' => 0, 'max' => 0],
        self::BUCKET_1_30 => ['min' => 1, 'max' => 30],
        self::BUCKET_31_60 => ['min' => 31, 'max' => 60],
        self::BUCKET_61_90 => ['min' => 61, 'max' => 90],
        self::BUCKET_91_180 => ['min' => 91, 'max' => 180],
        self::BUCKET_180_PLUS => ['min' => 181, 'max' => PHP_INT_MAX],
    ];

    // ─── Tier Constants (FR-CE-004) ─────────────────────────────
    public const TIER_OFFICER = 1;

    public const TIER_SUPERVISOR = 2;

    public const TIER_MANAGER = 3;

    public const TIER_LEGAL = 4;

    public const TIERS = [
        self::TIER_OFFICER => 'Officer',
        self::TIER_SUPERVISOR => 'Supervisor',
        self::TIER_MANAGER => 'Manager',
        self::TIER_LEGAL => 'Legal',
    ];

    /** @var array<int, int> DPD at which tier is entered for auto-escalation */
    public const TIER_THRESHOLDS = [
        self::TIER_OFFICER => 1,
        self::TIER_SUPERVISOR => 31,
        self::TIER_MANAGER => 61,
        self::TIER_LEGAL => 91,
    ];

    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_WRITTEN_OFF = 'written_off';

    public const STATUS_LEGAL = 'legal';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_WRITTEN_OFF => 'Written Off',
        self::STATUS_LEGAL => 'Legal',
    ];

    protected $fillable = [
        'loan_id',
        'loan_number',
        'member_name',
        'member_id',
        'dpd',
        'arrears_amount',
        'outstanding_balance',
        'instalment_amount',
        'delinquency_bucket',
        'tier',
        'previous_tier',
        'officer_id',
        'officer_name',
        'branch_code',
        'penalty_rate',
        'accrued_penalty',
        'status',
        'last_payment_date',
        'next_due_date',
        'escalated_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dpd' => 'integer',
            'tier' => 'integer',
            'previous_tier' => 'integer',
            'arrears_amount' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'instalment_amount' => 'decimal:2',
            'penalty_rate' => 'decimal:2',
            'accrued_penalty' => 'decimal:2',
            'last_payment_date' => 'date',
            'next_due_date' => 'date',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function activities(): HasMany
    {
        return $this->hasMany(CollectionsActivity::class, 'worklist_id');
    }

    public function ptpRecords(): HasMany
    {
        return $this->hasMany(PtpRecord::class, 'worklist_id');
    }

    public function demandLetters(): HasMany
    {
        return $this->hasMany(DemandLetter::class, 'worklist_id');
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'worklist_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForOfficer(Builder $query, int $officerId): void
    {
        $query->where('officer_id', $officerId);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInBucket(Builder $query, string $bucket): void
    {
        $query->where('delinquency_bucket', $bucket);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInTier(Builder $query, int $tier): void
    {
        $query->where('tier', $tier);
    }

    // ─── Delinquency Helpers (FR-CE-001) ────────────────────────

    /**
     * Classify DPD into bucket.
     */
    public static function classifyBucket(int $dpd): string
    {
        foreach (self::BUCKET_RANGES as $bucket => $range) {
            if ($dpd >= $range['min'] && $dpd <= $range['max']) {
                return $bucket;
            }
        }

        return self::BUCKET_180_PLUS;
    }

    /**
     * Reclassify this entry's delinquency bucket based on DPD.
     */
    public function reclassify(): void
    {
        $this->update([
            'delinquency_bucket' => self::classifyBucket($this->dpd),
        ]);
    }

    // ─── Penalty (FR-CE-002) ────────────────────────────────────

    /**
     * Compute daily penalty.
     */
    public function computeDailyPenalty(): float
    {
        if ((float) $this->penalty_rate <= 0 || (float) $this->arrears_amount <= 0) {
            return 0.0;
        }

        return round((float) $this->arrears_amount * ((float) $this->penalty_rate / 100 / 365), 2);
    }

    /**
     * Accrue one day of penalty.
     */
    public function accruePenalty(): void
    {
        $daily = $this->computeDailyPenalty();
        if ($daily > 0) {
            $this->increment('accrued_penalty', $daily);
        }
    }

    // ─── Escalation (FR-CE-004) ─────────────────────────────────

    /**
     * Determine the appropriate tier based on DPD.
     */
    public function determineEscalationTier(): int
    {
        if ($this->dpd >= self::TIER_THRESHOLDS[self::TIER_LEGAL]) {
            return self::TIER_LEGAL;
        }
        if ($this->dpd >= self::TIER_THRESHOLDS[self::TIER_MANAGER]) {
            return self::TIER_MANAGER;
        }
        if ($this->dpd >= self::TIER_THRESHOLDS[self::TIER_SUPERVISOR]) {
            return self::TIER_SUPERVISOR;
        }

        return self::TIER_OFFICER;
    }

    /**
     * Auto-escalate if DPD exceeds current tier threshold.
     */
    public function autoEscalate(): bool
    {
        $newTier = $this->determineEscalationTier();

        if ($newTier > $this->tier) {
            $this->update([
                'previous_tier' => $this->tier,
                'tier' => $newTier,
                'escalated_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Resolve the worklist entry.
     */
    public function resolve(): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }
}
