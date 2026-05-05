<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Member;
use App\Models\Tenant\MemberDocument;
use App\Models\Tenant\MemberShare;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // 30 active members with KYC documents and shares
        Member::factory()
            ->count(30)
            ->active()
            ->create()
            ->each(function (Member $member) {
                $this->seedDocuments($member, 'verified');
                $this->seedShares($member);
                $member->recalculateKycScore();
            });

        // 10 applicants with partial documents
        Member::factory()
            ->count(10)
            ->applicant()
            ->create()
            ->each(function (Member $member) {
                $this->seedDocuments($member, 'pending', 2);
            });

        // 5 dormant members
        Member::factory()
            ->count(5)
            ->dormant()
            ->create();

        // 3 suspended members
        Member::factory()
            ->count(3)
            ->suspended()
            ->create();

        // 1 exited, 1 deceased
        Member::factory()->exited()->create();
        Member::factory()->deceased()->create();

        $this->command->info('Seeded '.Member::count().' members.');
    }

    /**
     * Seed KYC documents for a member.
     */
    protected function seedDocuments(Member $member, string $status = 'verified', int $count = 4): void
    {
        $types = array_keys(MemberDocument::TYPES);
        $selectedTypes = array_slice($types, 0, $count);

        foreach ($selectedTypes as $type) {
            MemberDocument::create([
                'member_id' => $member->id,
                'document_type' => $type,
                'file_path' => "documents/{$member->member_number}/{$type}.pdf",
                'upload_date' => now()->subDays(fake()->numberBetween(1, 180)),
                'expiry_date' => in_array($type, ['national_id']) ? now()->addYears(5) : null,
                'verification_status' => $status,
                'verified_by' => $status === 'verified' ? 1 : null,
                'verified_at' => $status === 'verified' ? now()->subDays(fake()->numberBetween(1, 30)) : null,
            ]);
        }
    }

    /**
     * Seed share record for a member.
     */
    protected function seedShares(Member $member): void
    {
        $sharesHeld = fake()->numberBetween(10, 500);
        $parValue = 10000; // UGX 10,000 per share

        MemberShare::create([
            'member_id' => $member->id,
            'shares_held' => $sharesHeld,
            'par_value' => $parValue,
            'total_value' => $sharesHeld * $parValue,
            'percentage_of_total' => 0.0000,
        ]);
    }
}
