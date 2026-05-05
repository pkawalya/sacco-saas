<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestAccrual extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'account_id',
        'product_id',
        'member_id',
        'accrual_date',
        'period_start',
        'period_end',
        'computation_method',
        'average_balance',
        'applicable_rate',
        'days_in_period',
        'accrual_amount',
        'posting_status',
        'posted_at',
        'transaction_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'average_balance' => 'decimal:2',
            'applicable_rate' => 'decimal:4',
            'accrual_amount' => 'decimal:4',
            'accrual_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'account_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SavingsProduct::class, 'product_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SavingsTransaction::class, 'transaction_id');
    }
}
