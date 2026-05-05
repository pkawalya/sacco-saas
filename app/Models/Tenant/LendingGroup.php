<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lending group for group lending (Sprint 4.5).
 *
 * @property int $id
 * @property string $group_code
 * @property string $group_name
 * @property string $liability_type
 * @property float $repayment_rate
 * @property int $cycle_number
 */
class LendingGroup extends Model
{
    public const LIABILITY_JOINT = 'joint';

    public const LIABILITY_INDIVIDUAL = 'individual';

    public const LIABILITY_HYBRID = 'hybrid';

    public const STATUSES = [
        'active' => 'Active',
        'probation' => 'Probation',
        'suspended' => 'Suspended',
        'graduated' => 'Graduated',
    ];

    protected $fillable = [
        'group_code',
        'group_name',
        'branch_code',
        'max_members',
        'min_members',
        'liability_type',
        'group_savings_balance',
        'repayment_rate',
        'cycle_number',
        'max_loan_per_member',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_savings_balance' => 'decimal:2',
            'repayment_rate' => 'decimal:2',
            'max_loan_per_member' => 'decimal:2',
        ];
    }

    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    public function getMemberCountAttribute(): int
    {
        return $this->groupMembers()->where('status', 'active')->count();
    }

    public function hasCapacity(): bool
    {
        return $this->member_count < $this->max_members;
    }
}
