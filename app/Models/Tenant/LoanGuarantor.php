<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property float $guaranteed_amount
 * @property float $locked_amount
 * @property string $status
 */
class LoanGuarantor extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RELEASED = 'released';

    public const STATUS_SUBSTITUTED = 'substituted';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_RELEASED => 'Released',
        self::STATUS_SUBSTITUTED => 'Substituted',
    ];

    protected $fillable = [
        'loan_id',
        'guarantor_member_id',
        'guaranteed_savings_account_id',
        'guaranteed_amount',
        'original_savings_balance',
        'locked_amount',
        'status',
        'released_date',
        'release_reason',
        'substituted_by_guarantor_id',
        'substituted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'guaranteed_amount' => 'decimal:2',
            'original_savings_balance' => 'decimal:2',
            'locked_amount' => 'decimal:2',
            'released_date' => 'date',
            'substituted_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function guarantorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id');
    }

    public function guaranteedSavingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'guaranteed_savings_account_id');
    }

    public function substitutedByGuarantor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'substituted_by_guarantor_id');
    }
}
