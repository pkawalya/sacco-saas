<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demand letter / legal notice (FR-CE-013, FR-CE-020).
 *
 * @property int $id
 * @property int $worklist_id
 * @property int $loan_id
 * @property string $loan_number
 * @property string $letter_type
 * @property string $reference_number
 * @property string|null $content
 * @property string $recipient_name
 * @property string|null $recipient_address
 * @property string $recipient_type
 * @property string $delivery_method
 * @property string $status
 */
class DemandLetter extends Model
{
    // ─── Letter Types ───────────────────────────────────────────
    public const TYPE_REMINDER = 'reminder';

    public const TYPE_FIRST_DEMAND = 'first_demand';

    public const TYPE_FINAL_DEMAND = 'final_demand';

    public const TYPE_LEGAL_NOTICE = 'legal_notice';

    public const TYPE_GUARANTOR_NOTICE = 'guarantor_notice';

    public const LETTER_TYPES = [
        self::TYPE_REMINDER => 'Reminder',
        self::TYPE_FIRST_DEMAND => 'First Demand',
        self::TYPE_FINAL_DEMAND => 'Final Demand',
        self::TYPE_LEGAL_NOTICE => 'Legal Notice',
        self::TYPE_GUARANTOR_NOTICE => 'Guarantor Notice',
    ];

    // ─── Status Constants ───────────────────────────────────────
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RETURNED = 'returned';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SENT => 'Sent',
        self::STATUS_ACKNOWLEDGED => 'Acknowledged',
        self::STATUS_RETURNED => 'Returned',
    ];

    protected $fillable = [
        'worklist_id',
        'loan_id',
        'loan_number',
        'letter_type',
        'reference_number',
        'content',
        'recipient_name',
        'recipient_address',
        'recipient_type',
        'delivery_method',
        'sent_date',
        'acknowledged_date',
        'status',
        'generated_by',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_date' => 'date',
            'acknowledged_date' => 'date',
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
        $query->where('letter_type', $type);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function markSent(?string $date = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_date' => $date ?? now()->toDateString(),
        ]);
    }
}
