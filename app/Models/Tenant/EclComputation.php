<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * ECL computation period summary (Sprint 4.1).
 *
 * @property int $id
 * @property string $computation_period
 * @property float $total_ead
 * @property float $total_ecl
 * @property float $coverage_ratio
 * @property bool $is_posted
 */
class EclComputation extends Model
{
    protected $fillable = [
        'computation_period',
        'computation_date',
        'total_ead',
        'total_ecl',
        'provision_amount',
        'stage_1_count',
        'stage_1_ecl',
        'stage_2_count',
        'stage_2_ecl',
        'stage_3_count',
        'stage_3_ecl',
        'coverage_ratio',
        'is_posted',
        'journal_reference',
        'posted_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'computation_date' => 'date',
            'total_ead' => 'decimal:2',
            'total_ecl' => 'decimal:2',
            'provision_amount' => 'decimal:2',
            'stage_1_ecl' => 'decimal:2',
            'stage_2_ecl' => 'decimal:2',
            'stage_3_ecl' => 'decimal:2',
            'coverage_ratio' => 'decimal:4',
            'is_posted' => 'boolean',
        ];
    }

    public function markPosted(string $journalRef, int $postedBy): void
    {
        $this->update([
            'is_posted' => true,
            'journal_reference' => $journalRef,
            'posted_by' => $postedBy,
        ]);
    }
}
