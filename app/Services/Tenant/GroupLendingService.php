<?php

namespace App\Services\Tenant;

use App\Models\Tenant\GroupMember;
use App\Models\Tenant\LendingGroup;
use Illuminate\Support\Str;

/**
 * Group lending service (Sprint 4.5).
 */
class GroupLendingService
{
    /**
     * Create a lending group.
     *
     * @param  array<string, mixed>  $data
     */
    public function createGroup(array $data): LendingGroup
    {
        $code = 'GRP-'.Str::upper(Str::random(6));

        return LendingGroup::create(array_merge($data, [
            'group_code' => $code,
            'status' => 'active',
        ]));
    }

    /**
     * Add a member to a group.
     *
     * @param  array<string, mixed>  $data
     */
    public function addMember(int $groupId, array $data): GroupMember
    {
        $group = LendingGroup::findOrFail($groupId);

        if (! $group->hasCapacity()) {
            throw new \RuntimeException('Group has reached maximum capacity.');
        }

        return GroupMember::create(array_merge($data, [
            'group_id' => $groupId,
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]));
    }

    /**
     * Get group performance summary.
     *
     * @return array{group_code: string, group_name: string, member_count: int, avg_repayment_rate: float, total_borrowed: float, total_repaid: float, cycle: int}
     */
    public function getGroupPerformance(int $groupId): array
    {
        $group = LendingGroup::findOrFail($groupId);
        $members = $group->groupMembers()->where('status', 'active')->get();

        return [
            'group_code' => $group->group_code,
            'group_name' => $group->group_name,
            'member_count' => $members->count(),
            'avg_repayment_rate' => round($members->avg('personal_repayment_rate') ?? 0, 2),
            'total_borrowed' => round($members->sum('total_borrowed'), 2),
            'total_repaid' => round($members->sum('total_repaid'), 2),
            'cycle' => $group->cycle_number,
        ];
    }

    /**
     * Advance group to next lending cycle.
     */
    public function advanceCycle(int $groupId): LendingGroup
    {
        $group = LendingGroup::findOrFail($groupId);
        $group->increment('cycle_number');

        return $group->fresh();
    }
}
