<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * Generate member number based on format.
     */
    protected function generateMemberNumber(string $format): string
    {
        $year = date('y'); // 2 digits
        $sequence = Member::count() + 1; // Next sequence

        $number = str_replace('{year}', $year, $format);
        $number = preg_replace_callback('/\{sequence:(\d+)\}/', function ($matches) use ($sequence) {
            return str_pad($sequence, (int) $matches[1], '0', STR_PAD_LEFT);
        }, $number);

        return $number;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branchCode = fake()->randomElement(['KLA', 'MBR', 'JJA', 'ENT', 'GLU']);
        $year = date('Y');
        $sequence = fake()->unique()->numerify('######');

        // Generate member number based on tenant format
        $format = tenancy()->tenant->member_number_format ?? 'MEM-{year}{sequence:6}';
        $memberNumber = $this->generateMemberNumber($format);

        return [
            'member_number' => $memberNumber ?? "{$branchCode}-{$year}-{$sequence}",
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.6)->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => 'Ugandan',
            'national_id_type' => 'national_id',
            'national_id_number' => 'CM'.fake()->unique()->numerify('#############'),
            'photo_path' => null,
            'physical_address' => fake()->streetAddress(),
            'village' => fake()->city(),
            'cell' => fake()->word(),
            'district' => fake()->randomElement(['Kampala', 'Wakiso', 'Mukono', 'Jinja', 'Mbale', 'Mbarara', 'Gulu', 'Lira']),
            'postal_address' => fake()->optional(0.5)->postcode(),
            'primary_phone' => '07'.fake()->unique()->numerify('########'),
            'secondary_phone' => fake()->optional(0.3)->numerify('07########'),
            'email' => fake()->optional(0.6)->safeEmail(),
            'occupation' => fake()->jobTitle(),
            'employer_name' => fake()->optional(0.7)->company(),
            'monthly_income_range' => fake()->randomElement(['below_500k', '500k_1m', '1m_3m', '3m_5m', 'above_5m']),
            'nok_name' => fake()->name(),
            'nok_relationship' => fake()->randomElement(['spouse', 'parent', 'sibling', 'child', 'friend']),
            'nok_contact' => '07'.fake()->numerify('########'),
            'member_category' => 'individual',
            'referral_source' => fake()->optional(0.4)->randomElement(['walk_in', 'referral', 'social_media', 'ngo_partner', 'employer']),
            'kyc_score' => fake()->numberBetween(0, 100),
            'kyc_threshold' => 70,
            'branch_code' => $branchCode,
            'status' => Member::STATUS_ACTIVE,
            'registered_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'dormant_at' => null,
        ];
    }

    /**
     * Member is an applicant (newly registered, not yet approved).
     */
    public function applicant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_APPLICANT,
            'kyc_score' => fake()->numberBetween(0, 50),
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Member is active with full KYC.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_ACTIVE,
            'kyc_score' => fake()->numberBetween(70, 100),
            'approved_at' => now()->subDays(fake()->numberBetween(1, 365)),
        ]);
    }

    /**
     * Member is dormant.
     */
    public function dormant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_DORMANT,
            'dormant_at' => now()->subDays(fake()->numberBetween(30, 365)),
        ]);
    }

    /**
     * Member is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_SUSPENDED,
        ]);
    }

    /**
     * Member has exited.
     */
    public function exited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_EXITED,
        ]);
    }

    /**
     * Member is deceased.
     */
    public function deceased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_DECEASED,
        ]);
    }

    /**
     * Member has a low KYC score (incomplete).
     */
    public function kycIncomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_score' => fake()->numberBetween(0, 60),
            'kyc_threshold' => 70,
        ]);
    }
}
