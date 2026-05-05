<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApproval extends Model
{
    use HasFactory;

    public const DECISION_APPROVED = 'approved';

    public const DECISION_DECLINED = 'declined';

    public const DECISION_QUERIED = 'queried';

    public const DECISION_DEFERRED = 'deferred';

    public const DECISIONS = [
        self::DECISION_APPROVED => 'Approved',
        self::DECISION_DECLINED => 'Declined',
        self::DECISION_QUERIED => 'Queried',
        self::DECISION_DEFERRED => 'Deferred',
    ];

    protected $fillable = [
        'loan_id',
        'approval_level',
        'approver_id',
        'role',
        'decision',
        'amount_approved',
        'notes',
        'decided_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_approved' => 'decimal:2',
            'decided_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
