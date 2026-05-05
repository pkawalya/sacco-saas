<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Group member with performance tracking (Sprint 4.5).
 *
 * @property int $id
 * @property int $group_id
 * @property int $member_id
 * @property string $role
 * @property float $personal_repayment_rate
 */
class GroupMember extends Model
{
    public const ROLES = [
        'chairperson' => 'Chairperson',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
        'member' => 'Member',
    ];

    protected $fillable = [
        'group_id',
        'member_id',
        'member_name',
        'role',
        'personal_repayment_rate',
        'loans_taken',
        'loans_defaulted',
        'total_borrowed',
        'total_repaid',
        'status',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_repayment_rate' => 'decimal:2',
            'total_borrowed' => 'decimal:2',
            'total_repaid' => 'decimal:2',
            'joined_at' => 'date',
        ];
    }

    public function lendingGroup(): BelongsTo
    {
        return $this->belongsTo(LendingGroup::class, 'group_id');
    }

    public function getDefaultRateAttribute(): float
    {
        if ($this->loans_taken === 0) {
            return 0;
        }

        return round(($this->loans_defaulted / $this->loans_taken) * 100, 2);
    }
}
