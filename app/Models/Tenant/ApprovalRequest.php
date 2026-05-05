<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Maker-checker approval request.
 *
 * @property int $id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property string $action
 * @property array|null $payload
 * @property string $status
 */
class ApprovalRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'action',
        'payload',
        'requested_by',
        'reviewed_by',
        'status',
        'review_notes',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, self>
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Approve this request.
     */
    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject this request.
     */
    public function reject(int $reviewerId, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'review_notes' => $reason,
            'reviewed_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
