<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * IFRS 9 ECL staging per loan (Sprint 4.1).
 *
 * @property int $id
 * @property int $loan_id
 * @property string $loan_number
 * @property int $stage
 * @property float $pd
 * @property float $lgd
 * @property float $ead
 * @property float $ecl_amount
 */
class EclStaging extends Model
{
    protected $table = 'ecl_staging';

    public const STAGE_PERFORMING = 1;

    public const STAGE_SIGNIFICANT_INCREASE = 2;

    public const STAGE_CREDIT_IMPAIRED = 3;

    public const STAGES = [
        self::STAGE_PERFORMING => 'Stage 1 – Performing',
        self::STAGE_SIGNIFICANT_INCREASE => 'Stage 2 – Significant Increase',
        self::STAGE_CREDIT_IMPAIRED => 'Stage 3 – Credit-Impaired',
    ];

    /** @var array<int, int> DPD thresholds for staging */
    public const STAGE_THRESHOLDS = [
        self::STAGE_PERFORMING => 0,
        self::STAGE_SIGNIFICANT_INCREASE => 31,
        self::STAGE_CREDIT_IMPAIRED => 91,
    ];

    protected $fillable = [
        'loan_id',
        'loan_number',
        'stage',
        'previous_stage',
        'stage_changed_at',
        'pd',
        'lgd',
        'ead',
        'dpd',
        'ecl_amount',
        'computation_period',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pd' => 'decimal:6',
            'lgd' => 'decimal:6',
            'ead' => 'decimal:2',
            'ecl_amount' => 'decimal:2',
            'stage_changed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForPeriod(Builder $query, string $period): void
    {
        $query->where('computation_period', $period);
    }

    /**
     * Determine IFRS 9 stage from DPD.
     */
    public static function determineStage(int $dpd): int
    {
        if ($dpd >= self::STAGE_THRESHOLDS[self::STAGE_CREDIT_IMPAIRED]) {
            return self::STAGE_CREDIT_IMPAIRED;
        }
        if ($dpd >= self::STAGE_THRESHOLDS[self::STAGE_SIGNIFICANT_INCREASE]) {
            return self::STAGE_SIGNIFICANT_INCREASE;
        }

        return self::STAGE_PERFORMING;
    }

    /**
     * Compute ECL = PD × LGD × EAD.
     */
    public function computeEcl(): float
    {
        $ecl = round((float) $this->pd * (float) $this->lgd * (float) $this->ead, 2);
        $this->update(['ecl_amount' => $ecl]);

        return $ecl;
    }
}
