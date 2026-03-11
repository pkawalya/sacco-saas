<?php

namespace Database\Factories\Central;

use App\Models\Central\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word.' Plan',
            'slug' => $this->faker->unique()->slug,
            'price' => $this->faker->randomFloat(2, 10, 100),
            'currency' => 'UGX',
            'billing_cycle' => 'monthly',
            'duration_months' => 1,
            'description' => $this->faker->sentence,
            'modules' => array_keys(config('modules', [])),
            'stage' => 1,
            'is_active' => true,
            'is_custom' => false,
        ];
    }

    /**
     * Plan with only Stage 1 modules.
     */
    public function stage1(): static
    {
        return $this->state(fn (): array => [
            'stage' => 1,
            'modules' => collect(config('modules', []))
                ->filter(fn (array $module): bool => $module['stage'] <= 1)
                ->keys()
                ->toArray(),
        ]);
    }

    /**
     * Plan with Stage 1 + Stage 2 modules.
     */
    public function stage2(): static
    {
        return $this->state(fn (): array => [
            'stage' => 2,
            'modules' => collect(config('modules', []))
                ->filter(fn (array $module): bool => $module['stage'] <= 2)
                ->keys()
                ->toArray(),
        ]);
    }
}
