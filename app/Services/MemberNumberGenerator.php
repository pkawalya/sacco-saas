<?php

namespace App\Services;

use App\Models\Tenant\Member;

/**
 * Generates unique member numbers in the format: [BRANCH]-[YEAR]-[6-DIGIT SEQUENCE]
 * Example: KLA-2026-004521
 */
class MemberNumberGenerator
{
    public static function generate(?string $branchCode = null): string
    {
        $branch = strtoupper($branchCode ?? 'HQ');
        $year = date('Y');
        $prefix = "{$branch}-{$year}-";

        $lastMember = Member::query()
            ->where('member_number', 'like', "{$prefix}%")
            ->orderByDesc('member_number')
            ->first();

        if ($lastMember) {
            $lastSequence = (int) substr($lastMember->member_number, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }
}
