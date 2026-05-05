<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmortisationSchedule extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'loan_id',
        'instalment_number',
        'due_date',
        'principal_due',
        'interest_due',
        'maintenance_fee_due',
        'total_due',
        'opening_balance',
        'closing_balance',
        'principal_paid',
        'interest_paid',
        'penalty_paid',
        'total_paid',
        'paid_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'principal_due' => 'decimal:2',
            'interest_due' => 'decimal:2',
            'maintenance_fee_due' => 'decimal:2',
            'total_due' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'principal_paid' => 'decimal:2',
            'interest_paid' => 'decimal:2',
            'penalty_paid' => 'decimal:2',
            'total_paid' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && $this->due_date->isPast();
    }

    public function balanceDue(): float
    {
        return round((float) $this->total_due - (float) $this->total_paid, 2);
    }
}
